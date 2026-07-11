<?php

namespace Tests\Feature;

use App\Enums\MemoryType;
use App\Enums\SkillGroupStatus;
use App\Enums\SkillStatus;
use App\Enums\ValidationStatus;
use App\Models\Memory;
use App\Models\Skill;
use App\Models\SkillGroup;
use App\Services\Curation\AnthropicCurationEngine;
use App\Services\Curation\SkillCompiler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SkillCompilerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        config([
            'services.minimax.base_url' => 'https://fake.minimax.test/anthropic',
            'services.minimax.api_key' => 'test-key',
            'services.minimax.model' => 'MiniMax-M2.5',
        ]);
    }

    private function makeGroupWithMemories(): SkillGroup
    {
        $memoryA = Memory::create([
            'title' => 'Guardas hasColumn em migrations',
            'description' => 'Usar Schema::hasColumn evita duplicate column.',
            'type' => MemoryType::BEST_PRACTICE,
            'stack' => 'Laravel',
            'validation_status' => ValidationStatus::VALIDATED,
            'recurrence_count' => 4,
            'official_reference' => 'https://laravel.com/docs/13.x/migrations',
        ]);

        $memoryB = Memory::create([
            'title' => 'Drift entre migrations e schema',
            'description' => 'Bancos com drift quebram migrations não-idempotentes.',
            'type' => MemoryType::ERROR,
            'stack' => 'Laravel',
            'validation_status' => ValidationStatus::VALIDATED,
            'recurrence_count' => 3,
            'doc_validation_report' => [
                'sources' => ['https://laravel.com/docs/13.x/migrations#modifying-columns'],
            ],
        ]);

        $group = SkillGroup::create([
            'name' => 'Migrations Idempotentes',
            'slug' => 'migrations-idempotentes',
            'purpose' => 'Migrations resilientes a drift.',
            'rationale' => 'Mesmo momento operacional.',
            'cohesion' => 0.9,
            'status' => SkillGroupStatus::APPROVED,
        ]);

        $group->memories()->attach([$memoryA->id, $memoryB->id]);

        return $group;
    }

    private function fakeSkillResponse(SkillGroup $group): array
    {
        $memoryIds = $group->memories()->pluck('memories.id')->all();

        $candidate = [
            'schema_version' => '1.0',
            'slug' => 'laravel-migrations-idempotentes',
            'name' => 'Migrations Idempotentes no Laravel',
            'purpose' => 'Evitar duplicate column em bancos com drift.',
            'activation' => [
                'technologies' => ['php', 'laravel'],
                'triggers' => ['criação de migration que altera tabela existente'],
            ],
            'preconditions' => ['Projeto usa migrations.'],
            'workflow' => [
                ['order' => 1, 'action' => 'Guardar cada coluna com Schema::hasColumn.', 'validation' => 'Guarda por coluna.'],
            ],
            'guardrails' => ['Nunca assumir schema limpo.'],
            'anti_patterns' => ['Migration sem guarda em tabela existente.'],
            'evidence' => [
                'lesson_ids' => $memoryIds,
                'official_sources' => [
                    'https://laravel.com/docs/13.x/migrations',
                    'https://laravel.com/docs/13.x/migrations#modifying-columns',
                ],
            ],
            'test_cases' => [
                ['name' => 'Re-execução com drift', 'expected' => 'sem duplicate column'],
            ],
        ];

        return [
            'content' => [['type' => 'text', 'text' => json_encode($candidate)]],
            'usage' => ['input_tokens' => 900, 'output_tokens' => 500],
        ];
    }

    public function test_compiles_approved_group_into_draft_skill(): void
    {
        $group = $this->makeGroupWithMemories();
        Http::fake(['*' => Http::response($this->fakeSkillResponse($group))]);

        $skill = (new SkillCompiler(new AnthropicCurationEngine))->compile($group);

        $this->assertSame(SkillStatus::DRAFT, $skill->status);
        $this->assertSame('laravel-migrations-idempotentes', $skill->slug);
        $this->assertSame($group->id, $skill->skill_group_id);
        $this->assertSame(SkillGroupStatus::COMPILED, $group->fresh()->status);

        // Fontes agregadas: official_reference + doc_validation_report
        $this->assertCount(2, $skill->manifest['evidence']['official_sources']);
        $this->assertArrayHasKey('engine', $skill->manifest);

        Storage::disk('local')->assertExists('skills/laravel-migrations-idempotentes.md');
        $markdown = Storage::disk('local')->get('skills/laravel-migrations-idempotentes.md');
        $this->assertStringContainsString('## Workflow', $markdown);
        $this->assertStringContainsString('https://laravel.com/docs/13.x/migrations', $markdown);
    }

    public function test_recompilation_updates_same_skill_by_slug(): void
    {
        $group = $this->makeGroupWithMemories();
        Http::fake(['*' => Http::response($this->fakeSkillResponse($group))]);

        $compiler = new SkillCompiler(new AnthropicCurationEngine);
        $first = $compiler->compile($group);

        $group->update(['status' => SkillGroupStatus::APPROVED]);
        $second = $compiler->compile($group->fresh());

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Skill::count());
    }
}
