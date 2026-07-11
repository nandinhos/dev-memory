<?php

namespace Tests\Feature;

use App\Enums\DocumentationValidationStatus;
use App\Enums\MemoryType;
use App\Enums\ValidationStatus;
use App\Jobs\ValidateMemoryDocumentationJob;
use App\Models\Memory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ValidateMemoryDocumentationJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.minimax.base_url' => 'https://fake.minimax.test/anthropic',
            'services.minimax.api_key' => 'test-key',
            'services.minimax.model' => 'MiniMax-M2.5',
            'services.context7.base_url' => 'https://context7.test/api/v1',
            'services.context7.api_key' => null,
        ]);
    }

    private function makeMemory(array $overrides = []): Memory
    {
        return Memory::create(array_merge([
            'title' => 'ILIKE não funciona no SQLite',
            'description' => 'ILIKE é específico do PostgreSQL; usar LOWER + LIKE para portabilidade.',
            'type' => MemoryType::ERROR,
            'stack' => 'Laravel, PostgreSQL',
            'validation_status' => ValidationStatus::PENDING,
        ], $overrides));
    }

    private function fakeContext7(): array
    {
        return [
            'context7.test/api/v1/search*' => Http::response([
                'results' => [['id' => '/laravel/docs']],
            ]),
            'context7.test/api/v1/laravel/docs*' => Http::response(
                '### Case-insensitive search: ILIKE is PostgreSQL-specific; use LOWER() with LIKE for portability.'
            ),
        ];
    }

    private function fakeVerdictResponse(array $overrides = []): array
    {
        $verdict = array_merge([
            'status' => 'confirmed',
            'claims' => [
                ['claim' => 'ILIKE é específico do PostgreSQL', 'verdict' => 'supported', 'notes' => null],
            ],
            'version_constraints' => ['Laravel 13'],
            'confidence' => 0.92,
        ], $overrides);

        return [
            'content' => [['type' => 'text', 'text' => json_encode($verdict)]],
            'usage' => ['input_tokens' => 500, 'output_tokens' => 200],
        ];
    }

    public function test_confirmed_verdict_sets_status_and_auto_validates(): void
    {
        Http::fake(array_merge($this->fakeContext7(), [
            'fake.minimax.test/*' => Http::response($this->fakeVerdictResponse()),
        ]));

        $memory = $this->makeMemory();
        ValidateMemoryDocumentationJob::dispatchSync($memory);

        $memory->refresh();
        $this->assertSame(DocumentationValidationStatus::CONFIRMED, $memory->doc_validation_status);
        $this->assertSame(ValidationStatus::VALIDATED, $memory->validation_status);
        $this->assertSame('doc-pipeline (Context7 + MiniMax)', $memory->validated_by);
        $this->assertSame('/laravel/docs', $memory->doc_validation_report['library']);
        $this->assertNotNull($memory->doc_validated_at);
    }

    public function test_contradicted_verdict_never_auto_validates(): void
    {
        Http::fake(array_merge($this->fakeContext7(), [
            'fake.minimax.test/*' => Http::response($this->fakeVerdictResponse([
                'status' => 'contradicted',
                'confidence' => 0.95,
            ])),
        ]));

        $memory = $this->makeMemory();
        ValidateMemoryDocumentationJob::dispatchSync($memory);

        $memory->refresh();
        $this->assertSame(DocumentationValidationStatus::CONTRADICTED, $memory->doc_validation_status);
        $this->assertSame(ValidationStatus::PENDING, $memory->validation_status);
    }

    public function test_confirmed_with_low_confidence_stays_pending(): void
    {
        Http::fake(array_merge($this->fakeContext7(), [
            'fake.minimax.test/*' => Http::response($this->fakeVerdictResponse(['confidence' => 0.6])),
        ]));

        $memory = $this->makeMemory();
        ValidateMemoryDocumentationJob::dispatchSync($memory);

        $memory->refresh();
        $this->assertSame(DocumentationValidationStatus::CONFIRMED, $memory->doc_validation_status);
        $this->assertSame(ValidationStatus::PENDING, $memory->validation_status);
    }

    public function test_memory_without_stack_is_inconclusive_without_engine_call(): void
    {
        Http::fake();

        $memory = $this->makeMemory(['stack' => null]);
        ValidateMemoryDocumentationJob::dispatchSync($memory);

        $memory->refresh();
        $this->assertSame(DocumentationValidationStatus::INCONCLUSIVE, $memory->doc_validation_status);
        $this->assertStringContainsString('sem stack', $memory->doc_validation_report['note']);
        Http::assertNothingSent();
    }

    public function test_engine_failure_records_inconclusive_with_note(): void
    {
        Http::fake(array_merge($this->fakeContext7(), [
            'fake.minimax.test/*' => Http::response(['error' => 'overloaded'], 500),
        ]));

        $memory = $this->makeMemory();
        ValidateMemoryDocumentationJob::dispatchSync($memory);

        $memory->refresh();
        $this->assertSame(DocumentationValidationStatus::INCONCLUSIVE, $memory->doc_validation_status);
        $this->assertStringContainsString('falha do motor', $memory->doc_validation_report['note']);
        $this->assertSame(ValidationStatus::PENDING, $memory->validation_status);
    }
}
