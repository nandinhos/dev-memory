<?php

namespace Tests\Unit;

use App\Services\Curation\LessonDraft;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LessonDraftTest extends TestCase
{
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Erro de migration com coluna duplicada',
            'summary' => 'Migration re-adicionava colunas já criadas pela migration base.',
            'problem' => 'migrate falhava com duplicate column name',
            'root_cause' => 'colunas duplicadas entre migrations',
            'solution' => 'guardas Schema::hasColumn por coluna',
            'category' => 'error',
            'technologies' => [['name' => 'Laravel', 'version' => '13']],
            'evidence' => ['saída do migrate'],
            'applicability' => ['migrations incrementais'],
            'risks' => [],
            'confidence' => 0.95,
        ], $overrides);
    }

    public function test_creates_draft_from_valid_payload(): void
    {
        $draft = LessonDraft::fromArray($this->validPayload());

        $this->assertSame('error', $draft->category);
        $this->assertSame(0.95, $draft->confidence);
        $this->assertSame('Laravel', $draft->technologies[0]['name']);
    }

    public function test_accepts_null_root_cause_and_missing_key(): void
    {
        $draft = LessonDraft::fromArray($this->validPayload(['root_cause' => null]));
        $this->assertNull($draft->rootCause);

        $payload = $this->validPayload();
        unset($payload['root_cause']);
        $this->assertNull(LessonDraft::fromArray($payload)->rootCause);
    }

    public function test_rejects_missing_required_fields_listing_all(): void
    {
        try {
            LessonDraft::fromArray(['title' => 'Título válido de teste']);
            $this->fail('deveria ter lançado InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('summary', $e->getMessage());
            $this->assertStringContainsString('solution', $e->getMessage());
            $this->assertStringContainsString('confidence', $e->getMessage());
        }
    }

    public function test_rejects_invalid_category(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/category/');

        LessonDraft::fromArray($this->validPayload(['category' => 'jailbreak']));
    }

    public function test_accepts_expanded_categories(): void
    {
        foreach (['workaround', 'architecture_decision', 'anti_pattern'] as $category) {
            $draft = LessonDraft::fromArray($this->validPayload(['category' => $category]));
            $this->assertSame($category, $draft->category);
        }
    }

    public function test_rejects_confidence_out_of_range(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/confidence/');

        LessonDraft::fromArray($this->validPayload(['confidence' => 999]));
    }

    public function test_rejects_short_title(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/title/');

        LessonDraft::fromArray($this->validPayload(['title' => 'curto']));
    }

    public function test_rejects_malformed_technologies(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/technologies/');

        LessonDraft::fromArray($this->validPayload(['technologies' => ['Laravel']]));
    }

    public function test_to_array_round_trip(): void
    {
        $payload = $this->validPayload();
        $this->assertSame($payload, LessonDraft::fromArray($payload)->toArray());
    }
}
