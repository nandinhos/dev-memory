<?php

namespace Tests\Unit;

use App\Services\Curation\SkillCandidate;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SkillCandidateTest extends TestCase
{
    private array $memoryIds = ['mem-1', 'mem-2'];

    private array $sources = ['https://laravel.com/docs/13.x/migrations'];

    private function validPayload(): array
    {
        return [
            'schema_version' => '1.0',
            'slug' => 'laravel-migrations-idempotentes',
            'name' => 'Migrations Idempotentes',
            'purpose' => 'Evitar falhas de coluna duplicada em bancos com drift.',
            'activation' => [
                'technologies' => ['php', 'laravel'],
                'triggers' => ['criação de migration incremental'],
            ],
            'preconditions' => ['Projeto usa migrations do Laravel.'],
            'workflow' => [
                ['order' => 1, 'action' => 'Verificar colunas existentes com Schema::hasColumn.', 'validation' => 'Guarda presente por coluna.'],
            ],
            'guardrails' => ['Nunca re-adicionar coluna sem guarda.'],
            'anti_patterns' => ['Migration que assume schema limpo.'],
            'evidence' => [
                'lesson_ids' => ['mem-1'],
                'official_sources' => ['https://laravel.com/docs/13.x/migrations'],
            ],
            'test_cases' => [
                ['name' => 'Re-execução em banco com drift', 'expected' => 'migration completa sem duplicate column'],
            ],
        ];
    }

    public function test_parses_valid_candidate(): void
    {
        $candidate = SkillCandidate::fromArray($this->validPayload(), $this->memoryIds, $this->sources);

        $this->assertSame('laravel-migrations-idempotentes', $candidate->slug);
        $this->assertCount(1, $candidate->workflow);
    }

    public function test_rejects_invented_official_source(): void
    {
        $payload = $this->validPayload();
        $payload['evidence']['official_sources'] = ['https://laravel.com/docs/13.x/inventada'];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/referência inventada/');

        SkillCandidate::fromArray($payload, $this->memoryIds, $this->sources);
    }

    public function test_rejects_lesson_id_outside_group(): void
    {
        $payload = $this->validPayload();
        $payload['evidence']['lesson_ids'] = ['mem-de-outro-grupo'];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/não pertence às memórias do grupo/');

        SkillCandidate::fromArray($payload, $this->memoryIds, $this->sources);
    }

    public function test_accepts_empty_sources_when_none_provided(): void
    {
        $payload = $this->validPayload();
        $payload['evidence']['official_sources'] = [];

        $candidate = SkillCandidate::fromArray($payload, $this->memoryIds, []);

        $this->assertSame([], $candidate->evidence['official_sources']);
    }

    public function test_rejects_empty_workflow(): void
    {
        $payload = $this->validPayload();
        $payload['workflow'] = [];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/workflow/');

        SkillCandidate::fromArray($payload, $this->memoryIds, $this->sources);
    }

    public function test_rejects_activation_without_triggers(): void
    {
        $payload = $this->validPayload();
        $payload['activation']['triggers'] = [];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/activation/');

        SkillCandidate::fromArray($payload, $this->memoryIds, $this->sources);
    }
}
