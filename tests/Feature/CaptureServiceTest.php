<?php

namespace Tests\Feature;

use App\Enums\CaptureStatus;
use App\Models\Capture;
use App\Services\Curation\CaptureSanitizer;
use App\Services\Curation\CaptureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaptureServiceTest extends TestCase
{
    use RefreshDatabase;

    private CaptureService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CaptureService(new CaptureSanitizer);
    }

    public function test_ingest_creates_sanitized_capture_preserving_raw(): void
    {
        $raw = 'Bug corrigido. DB_PASSWORD=Secreta123 estava errada no .env';

        $capture = $this->service->ingest($raw, 'claude-code', 'bug_resolved', 'dev-memory');

        $this->assertSame($raw, $capture->raw_content);
        $this->assertStringNotContainsString('Secreta123', $capture->sanitized_content);
        $this->assertSame(CaptureStatus::SANITIZED, $capture->status);
        $this->assertArrayHasKey('env_assignment', $capture->metadata['redactions']);
    }

    public function test_duplicate_ingest_returns_existing_capture(): void
    {
        $raw = 'Mesma captura enviada duas vezes';

        $first = $this->service->ingest($raw, 'claude-code');
        $second = $this->service->ingest($raw, 'claude-code');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Capture::count());
    }

    public function test_whitespace_and_case_variations_are_still_duplicates(): void
    {
        $this->service->ingest("Erro   no  deploy\ncorrigido", 'claude-code');
        $this->service->ingest('erro no deploy corrigido', 'claude-code');

        $this->assertSame(1, Capture::count());
    }

    public function test_different_source_or_commit_creates_new_capture(): void
    {
        $raw = 'Mesma captura, contextos diferentes';

        $this->service->ingest($raw, 'claude-code');
        $this->service->ingest($raw, 'codex');
        $this->service->ingest($raw, 'claude-code', metadata: ['commit' => 'abc123']);

        $this->assertSame(3, Capture::count());
    }
}
