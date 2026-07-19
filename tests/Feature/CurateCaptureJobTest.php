<?php

namespace Tests\Feature;

use App\Enums\CaptureStatus;
use App\Enums\MemorySource;
use App\Enums\MemoryType;
use App\Enums\ValidationStatus;
use App\Jobs\CurateCaptureJob;
use App\Models\CurationExecution;
use App\Models\Memory;
use App\Services\Curation\CaptureSanitizer;
use App\Services\Curation\CaptureService;
use App\Services\MemoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class CurateCaptureJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.minimax.base_url' => 'https://fake.minimax.test/anthropic',
            'services.minimax.api_key' => 'test-key',
            'services.minimax.model' => 'MiniMax-M2.5',
        ]);
    }

    private function ingestCapture(string $content = 'Erro de migration corrigido com guardas hasColumn no Laravel 13.')
    {
        return (new CaptureService(new CaptureSanitizer))
            ->ingest($content, 'claude-code', 'bug_resolved', 'dev-memory-laravel');
    }

    private function fakeDraftResponse(array $overrides = []): array
    {
        $draft = array_merge([
            'title' => 'Migration idempotente com guardas hasColumn',
            'summary' => 'Guardas Schema::hasColumn evitam duplicate column em bancos com drift.',
            'problem' => 'migrate falhava com duplicate column name',
            'root_cause' => 'migration re-adicionava colunas existentes',
            'solution' => 'guarda hasColumn por coluna',
            'category' => 'error',
            'technologies' => [['name' => 'Laravel', 'version' => '13']],
            'evidence' => [],
            'applicability' => ['migrations incrementais'],
            'risks' => [],
            'confidence' => 0.9,
        ], $overrides);

        return [
            'content' => [['type' => 'text', 'text' => json_encode($draft)]],
            'usage' => ['input_tokens' => 150, 'output_tokens' => 300],
        ];
    }

    public function test_creates_pending_memory_and_records_execution(): void
    {
        Http::fake(['*' => Http::response($this->fakeDraftResponse())]);

        $capture = $this->ingestCapture();
        CurateCaptureJob::dispatchSync($capture);

        $capture->refresh();
        $this->assertSame(CaptureStatus::CURATED, $capture->status);
        $this->assertNotNull($capture->memory_id);

        $memory = Memory::find($capture->memory_id);
        $this->assertSame(MemoryType::ERROR, $memory->type);
        $this->assertSame(ValidationStatus::PENDING, $memory->validation_status);
        $this->assertSame(MemorySource::CAPTURE, $memory->source_system);
        $this->assertSame($capture->id, $memory->original_id);
        $this->assertStringContainsString('## Solução', $memory->description);

        $execution = CurationExecution::firstWhere('capture_id', $capture->id);
        $this->assertSame('completed', $execution->status);
        $this->assertSame('memory_created', $execution->outcome);
        $this->assertSame(hash('sha256', $capture->sanitized_content), $execution->input_hash);
        $this->assertNotNull($execution->output_hash);
        $this->assertSame(300, $execution->usage['output_tokens']);
    }

    public function test_deduplicates_against_existing_memory(): void
    {
        Http::fake(['*' => Http::response($this->fakeDraftResponse())]);

        $existing = Memory::create([
            'title' => 'Migration idempotente com guardas hasColumn',
            'description' => 'Guardas Schema::hasColumn por coluna evitam duplicate column ao rodar migrations em bancos com drift.',
            'type' => MemoryType::ERROR,
            'stack' => 'Laravel',
            'recurrence_count' => 2,
        ]);

        $capture = $this->ingestCapture();
        CurateCaptureJob::dispatchSync($capture);

        $this->assertSame(3, $existing->fresh()->recurrence_count);
        $this->assertSame(1, Memory::count());
        $this->assertSame($existing->id, $capture->fresh()->memory_id);
        $this->assertSame('deduplicated', CurationExecution::first()->outcome);
        $this->assertTrue($capture->fresh()->metadata['dedup']['independent']);
    }

    public function test_same_incident_links_capture_without_incrementing_recurrence(): void
    {
        Http::fake(['*' => Http::response($this->fakeDraftResponse())]);

        $existing = Memory::create([
            'title' => 'Migration idempotente com guardas hasColumn',
            'description' => 'Guardas Schema::hasColumn por coluna evitam duplicate column ao rodar migrations em bancos com drift.',
            'type' => MemoryType::ERROR,
            'stack' => 'Laravel',
            'recurrence_count' => 2,
        ]);

        $service = new CaptureService(new CaptureSanitizer);

        $previous = $service->ingest(
            'Primeira captura do incidente de migration',
            'claude-code', 'bug_resolved', 'dev-memory-laravel', ['commit' => 'abc123'],
        );
        $previous->update(['memory_id' => $existing->id]);

        $capture = $service->ingest(
            'Outro relato do mesmo bug de migration com duplicate column no drift',
            'claude-code', 'bug_resolved', 'dev-memory-laravel', ['commit' => 'abc123'],
        );
        CurateCaptureJob::dispatchSync($capture);

        $capture->refresh();
        $this->assertSame($existing->id, $capture->memory_id);
        $this->assertSame(2, $existing->fresh()->recurrence_count);
        $this->assertFalse($capture->metadata['dedup']['independent']);
    }

    public function test_discards_low_confidence_draft(): void
    {
        Http::fake(['*' => Http::response($this->fakeDraftResponse(['confidence' => 0.2]))]);

        $capture = $this->ingestCapture('deu erro ontem, resolvi mexendo em algo');
        CurateCaptureJob::dispatchSync($capture);

        $capture->refresh();
        $this->assertSame(CaptureStatus::DISCARDED, $capture->status);
        $this->assertNull($capture->memory_id);
        $this->assertSame(0, Memory::count());
        $this->assertNotEmpty($capture->metadata['discard_reasons']);
        $this->assertSame('discarded', CurationExecution::first()->outcome);
    }

    public function test_marks_capture_failed_when_engine_fails(): void
    {
        Sleep::fake(); // pula o backoff do retry (5xx é transiente)
        Http::fake(['*' => Http::response(['error' => 'overloaded'], 500)]);

        $capture = $this->ingestCapture();
        CurateCaptureJob::dispatchSync($capture);

        $capture->refresh();
        $this->assertSame(CaptureStatus::FAILED, $capture->status);
        $this->assertSame(0, Memory::count());

        $execution = CurationExecution::first();
        $this->assertSame('failed', $execution->status);
        $this->assertStringContainsString('HTTP 500', $execution->error);
    }

    public function test_records_failure_when_persistence_throws(): void
    {
        Http::fake(['*' => Http::response($this->fakeDraftResponse())]);

        // Simula um erro de persistência (ex.: coluna curta em Postgres) — que
        // NÃO é CurationFailedException. A rede de proteção deve registrar e
        // marcar FAILED em vez de deixar a exceção derrubar o worker.
        $this->mock(MemoryService::class, function ($mock) {
            $mock->shouldReceive('create')->andThrow(new \RuntimeException('value too long for type character varying(20)'));
        });

        $capture = $this->ingestCapture();
        CurateCaptureJob::dispatchSync($capture);

        $capture->refresh();
        $this->assertSame(CaptureStatus::FAILED, $capture->status);
        $this->assertSame(0, Memory::count());

        $execution = CurationExecution::first();
        $this->assertSame('failed', $execution->status);
        $this->assertStringContainsString('erro inesperado', $execution->error);
        $this->assertStringContainsString('value too long', $execution->error);
    }
}
