<?php

namespace App\Jobs;

use App\Enums\CaptureStatus;
use App\Enums\MemoryScope;
use App\Enums\MemorySource;
use App\Enums\ValidationStatus;
use App\Models\Capture;
use App\Models\CurationExecution;
use App\Services\Curation\CurationFailedException;
use App\Services\Curation\KnowledgePreparationEngine;
use App\Services\Curation\LessonDraft;
use App\Services\Curation\PromotionPolicy;
use App\Services\Curation\RecurrenceScorer;
use App\Services\MemoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CurateCaptureJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** O engine já faz os próprios reparos; sem retry do job. */
    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(
        public Capture $capture,
    ) {}

    public function handle(
        KnowledgePreparationEngine $engine,
        PromotionPolicy $policy,
        MemoryService $memories,
        RecurrenceScorer $scorer,
    ): void {
        $input = $this->capture->sanitized_content ?? '';
        $startedAt = microtime(true);

        try {
            $draft = $engine->prepare($input);
        } catch (CurationFailedException $e) {
            $this->recordExecution($engine, $input, $startedAt, 'failed', null, $e->getMessage());
            $this->capture->update(['status' => CaptureStatus::FAILED]);

            return;
        }

        $decision = $policy->evaluate($draft);

        if (! $decision->persist) {
            $this->recordExecution($engine, $input, $startedAt, 'completed', 'discarded');
            $this->capture->update([
                'status' => CaptureStatus::DISCARDED,
                'metadata' => array_merge($this->capture->metadata ?? [], [
                    'discard_reasons' => $decision->reasons,
                    'draft_confidence' => $draft->confidence,
                ]),
            ]);

            return;
        }

        $match = $scorer->findMatch($draft, $this->capture);

        if ($match !== null) {
            if ($match->independent) {
                $memories->incrementRecurrence($match->memory);
            }

            $this->recordExecution($engine, $input, $startedAt, 'completed', 'deduplicated', draft: $draft);
            $this->capture->update([
                'status' => CaptureStatus::CURATED,
                'memory_id' => $match->memory->id,
                'metadata' => array_merge($this->capture->metadata ?? [], [
                    'dedup' => $match->score->toArray() + ['independent' => $match->independent],
                ]),
            ]);

            return;
        }

        $memory = $memories->create([
            'title' => $draft->title,
            'description' => $this->buildDescription($draft),
            'type' => $draft->category,
            'stack' => $this->buildStack($draft),
            'scope' => MemoryScope::PROJECT,
            'validation_status' => ValidationStatus::PENDING,
            'source_system' => MemorySource::CAPTURE,
            'source_project' => $this->capture->source_project,
            'original_id' => $this->capture->id,
        ]);

        $this->recordExecution($engine, $input, $startedAt, 'completed', 'memory_created', draft: $draft);
        $this->capture->update([
            'status' => CaptureStatus::CURATED,
            'memory_id' => $memory->id,
        ]);
    }

    /**
     * Última rede de proteção: exceção inesperada ou kill por timeout.
     * Sem isto a capture ficaria presa em 'sanitized' para sempre
     * (recuperável depois via memory:process-captures --retry-failed).
     */
    public function failed(?\Throwable $exception): void
    {
        $this->capture->update(['status' => CaptureStatus::FAILED]);
    }

    private function buildDescription(LessonDraft $draft): string
    {
        $parts = [$draft->summary];
        $parts[] = "## Problema\n{$draft->problem}";

        if ($draft->rootCause !== null) {
            $parts[] = "## Causa raiz\n{$draft->rootCause}";
        }

        $parts[] = "## Solução\n{$draft->solution}";

        if ($draft->risks !== []) {
            $parts[] = "## Riscos e limitações\n- ".implode("\n- ", $draft->risks);
        }

        if ($draft->applicability !== []) {
            $parts[] = "## Aplicabilidade\n- ".implode("\n- ", $draft->applicability);
        }

        return implode("\n\n", $parts);
    }

    private function buildStack(LessonDraft $draft): ?string
    {
        $names = array_column($draft->technologies, 'name');

        return $names === [] ? null : implode(', ', $names);
    }

    private function recordExecution(
        KnowledgePreparationEngine $engine,
        string $input,
        float $startedAt,
        string $status,
        ?string $outcome,
        ?string $error = null,
        ?LessonDraft $draft = null,
    ): void {
        $meta = $engine->lastMeta();

        CurationExecution::create([
            'capture_id' => $this->capture->id,
            'pipeline_stage' => 'lesson_preparation',
            'provider' => $meta['provider'],
            'model' => $meta['model'],
            'prompt_version' => $meta['prompt_version'],
            'temperature' => $meta['temperature'],
            'input_hash' => hash('sha256', $input),
            'output_hash' => $draft !== null
                ? hash('sha256', json_encode($draft->toArray()))
                : null,
            'attempts' => $meta['attempts'],
            'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            'usage' => $meta['usage'],
            'status' => $status,
            'outcome' => $outcome,
            'error' => $error,
        ]);
    }
}
