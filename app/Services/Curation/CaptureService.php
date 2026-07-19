<?php

namespace App\Services\Curation;

use App\Enums\CaptureStatus;
use App\Models\Capture;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Immutable capture ingestion: raw content is never rewritten, sanitized
 * content lives alongside it, and the idempotency key prevents the same
 * event (re-imports, double seeds, retries) from creating duplicates.
 */
class CaptureService
{
    public function __construct(
        private CaptureSanitizer $sanitizer,
    ) {}

    public function ingest(
        string $rawContent,
        string $sourceSystem,
        ?string $triggerEvent = null,
        ?string $sourceProject = null,
        array $metadata = [],
    ): Capture {
        $key = $this->idempotencyKey($rawContent, $sourceSystem, $sourceProject, $metadata);

        $existing = Capture::where('idempotency_key', $key)->first();

        if ($existing !== null) {
            return $existing;
        }

        $result = $this->sanitizer->sanitize($rawContent);

        try {
            return Capture::create([
                'source_system' => $sourceSystem,
                'trigger_event' => $triggerEvent,
                'source_project' => $sourceProject,
                'raw_content' => $rawContent,
                'sanitized_content' => $result->text,
                'metadata' => array_merge($metadata, ['redactions' => $result->redactions]),
                'idempotency_key' => $key,
                'status' => CaptureStatus::SANITIZED,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Corrida entre requests simultâneas com o mesmo conteúdo: a outra
            // venceu o check-then-create — devolve a capture dela (idempotência real).
            return Capture::where('idempotency_key', $key)->firstOrFail();
        }
    }

    public function idempotencyKey(
        string $rawContent,
        string $sourceSystem,
        ?string $sourceProject,
        array $metadata = [],
    ): string {
        $normalized = mb_strtolower(preg_replace('/\s+/', ' ', trim($rawContent)));

        return hash('sha256', implode('|', [
            $sourceSystem,
            $sourceProject ?? '',
            $normalized,
            $metadata['commit'] ?? '',
        ]));
    }
}
