<?php

namespace Tests\Feature;

use App\Enums\MemoryType;
use App\Enums\SkillGroupStatus;
use App\Enums\ValidationStatus;
use App\Models\Memory;
use App\Models\SkillGroup;
use App\Services\Curation\AnthropicCurationEngine;
use App\Services\Curation\SkillGroupProposer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SkillGroupProposerTest extends TestCase
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

    private function makeCandidate(string $title): Memory
    {
        return Memory::create([
            'title' => $title,
            'description' => 'Descrição técnica de '.$title,
            'type' => MemoryType::LESSON,
            'stack' => 'Laravel',
            'validation_status' => ValidationStatus::VALIDATED,
            'recurrence_count' => 4,
        ]);
    }

    private function fakeProposalResponse(array $proposal): array
    {
        return [
            'content' => [['type' => 'text', 'text' => json_encode($proposal)]],
            'usage' => ['input_tokens' => 800, 'output_tokens' => 400],
        ];
    }

    public function test_proposes_and_stores_groups_with_pivot(): void
    {
        $a = $this->makeCandidate('Memória A sobre Alpine');
        $b = $this->makeCandidate('Memória B sobre Alpine');
        $c = $this->makeCandidate('Memória C isolada');

        Http::fake([
            '*' => Http::response($this->fakeProposalResponse([
                'groups' => [[
                    'name' => 'Grupo Alpine',
                    'slug' => 'grupo-alpine',
                    'purpose' => 'Integração Alpine',
                    'rationale' => 'Mesmo momento operacional',
                    'cohesion' => 0.85,
                    'memory_ids' => [$a->id, $b->id],
                ]],
                'standalone' => [['memory_id' => $c->id, 'reason' => 'independente']],
                'excluded' => [],
            ])),
        ]);

        $proposer = new SkillGroupProposer(new AnthropicCurationEngine);
        $candidates = Memory::skillCandidates()->get();
        $proposal = $proposer->propose($candidates);
        $groups = $proposer->store($proposal);

        $this->assertCount(1, $groups);

        $group = SkillGroup::firstWhere('slug', 'grupo-alpine');
        $this->assertSame(SkillGroupStatus::PROPOSED, $group->status);
        $this->assertSame(0.85, $group->cohesion);
        $this->assertEqualsCanonicalizing(
            [$a->id, $b->id],
            $group->memories()->pluck('memories.id')->all(),
        );
        $this->assertCount(1, $a->skillGroups);
    }

    public function test_store_replaces_proposed_but_preserves_approved(): void
    {
        $a = $this->makeCandidate('Memória A');
        $b = $this->makeCandidate('Memória B');

        $approved = SkillGroup::create([
            'name' => 'Grupo aprovado',
            'slug' => 'grupo-aprovado',
            'purpose' => 'x',
            'rationale' => 'y',
            'cohesion' => 0.9,
            'status' => SkillGroupStatus::APPROVED,
        ]);

        $old = SkillGroup::create([
            'name' => 'Proposta antiga',
            'slug' => 'proposta-antiga',
            'purpose' => 'x',
            'rationale' => 'y',
            'cohesion' => 0.5,
            'status' => SkillGroupStatus::PROPOSED,
        ]);

        Http::fake([
            '*' => Http::response($this->fakeProposalResponse([
                'groups' => [[
                    'name' => 'Proposta nova',
                    'slug' => 'proposta-nova',
                    'purpose' => 'p',
                    'rationale' => 'r',
                    'cohesion' => 0.7,
                    'memory_ids' => [$a->id, $b->id],
                ]],
                'standalone' => [],
                'excluded' => [],
            ])),
        ]);

        $proposer = new SkillGroupProposer(new AnthropicCurationEngine);
        $proposer->store($proposer->propose(Memory::skillCandidates()->get()));

        $this->assertNull(SkillGroup::find($old->id));
        $this->assertNotNull(SkillGroup::find($approved->id));
        $this->assertNotNull(SkillGroup::firstWhere('slug', 'proposta-nova'));
    }

    public function test_skill_candidates_scope_filters_correctly(): void
    {
        $this->makeCandidate('Candidata válida');

        Memory::create([
            'title' => 'Validada mas recorrência baixa',
            'description' => 'x',
            'type' => MemoryType::LESSON,
            'validation_status' => ValidationStatus::VALIDATED,
            'recurrence_count' => 1,
        ]);

        Memory::create([
            'title' => 'Recorrente mas pendente',
            'description' => 'x',
            'type' => MemoryType::LESSON,
            'validation_status' => ValidationStatus::PENDING,
            'recurrence_count' => 9,
        ]);

        $this->assertSame(1, Memory::skillCandidates()->count());
    }
}
