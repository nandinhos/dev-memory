<?php

namespace Tests\Feature;

use App\Enums\KnowledgeEdgeStatus;
use App\Enums\KnowledgeRelationType;
use App\Enums\MemoryScope;
use App\Enums\MemoryType;
use App\Enums\ValidationStatus;
use App\Livewire\MemoryDetail;
use App\Models\KnowledgeEdge;
use App\Models\KnowledgeNode;
use App\Models\Memory;
use App\Models\User;
use App\Services\KnowledgeGraph\KnowledgeGraphProjector;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MemoryDetailTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ações de curadoria (validar/promover/excluir) exigem admin (RBAC).
        $this->actingAs(User::factory()->create());
    }

    private function makeMemory(array $attrs = []): Memory
    {
        return Memory::factory()->create(array_merge([
            'type' => MemoryType::ERROR,
            'scope' => MemoryScope::PROJECT,
            'validation_status' => ValidationStatus::PENDING,
        ], $attrs));
    }

    public function test_increment_recurrence_dispatches_toast(): void
    {
        $memory = $this->makeMemory();

        Livewire::test(MemoryDetail::class, ['memory' => $memory])
            ->call('incrementRecurrence')
            ->assertDispatched('show-toast');
    }

    public function test_mark_as_validated_dispatches_toast(): void
    {
        $memory = $this->makeMemory(['validation_status' => ValidationStatus::PENDING]);

        Livewire::test(MemoryDetail::class, ['memory' => $memory])
            ->call('markAsValidated')
            ->assertDispatched('show-toast');
    }

    public function test_increment_recurrence_increases_count(): void
    {
        $memory = $this->makeMemory(['recurrence_count' => 1]);

        Livewire::test(MemoryDetail::class, ['memory' => $memory])
            ->call('incrementRecurrence');

        $this->assertEquals(2, $memory->fresh()->recurrence_count);
    }

    public function test_mark_as_validated_updates_status(): void
    {
        $memory = $this->makeMemory(['validation_status' => ValidationStatus::PENDING]);

        Livewire::test(MemoryDetail::class, ['memory' => $memory])
            ->call('markAsValidated');

        $this->assertEquals(
            ValidationStatus::VALIDATED,
            $memory->fresh()->validation_status
        );
    }

    public function test_promote_unvalidated_memory_shows_error_toast_without_changing_scope(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $memory = $this->makeMemory(['validation_status' => ValidationStatus::PENDING]);

        Livewire::test(MemoryDetail::class, ['memory' => $memory])
            ->call('promoteToGlobal')
            ->assertDispatched('show-toast', type: 'erro');

        $this->assertSame(MemoryScope::PROJECT, $memory->fresh()->scope);
    }

    public function test_renders_knowledge_graph_section_and_related_memories(): void
    {
        $root = $this->makeMemory(['title' => 'Raiz Causal', 'scope' => MemoryScope::GLOBAL, 'validation_status' => ValidationStatus::VALIDATED]);
        $target = $this->makeMemory(['title' => 'Solução Relacionada', 'scope' => MemoryScope::GLOBAL, 'validation_status' => ValidationStatus::VALIDATED]);

        $projector = app(KnowledgeGraphProjector::class);
        $projector->projectMemory($root);
        $projector->projectMemory($target);

        $nodeA = KnowledgeNode::where('memory_id', $root->id)->first();
        $nodeB = KnowledgeNode::where('memory_id', $target->id)->first();

        KnowledgeEdge::factory()->create([
            'source_node_id' => $nodeB->id,
            'target_node_id' => $nodeA->id,
            'relation_type' => KnowledgeRelationType::RESOLVES,
            'status' => KnowledgeEdgeStatus::VALIDATED,
        ]);

        Livewire::test(MemoryDetail::class, ['memory' => $root])
            ->assertSee('KNOWLEDGE GRAPH')
            ->assertSee('Solução Relacionada')
            ->assertSee('resolves');
    }

    public function test_toggles_graph_tab_between_relations_and_mermaid(): void
    {
        $memory = $this->makeMemory();

        Livewire::test(MemoryDetail::class, ['memory' => $memory])
            ->assertSet('graphTab', 'relations')
            ->call('setGraphTab', 'mermaid')
            ->assertSet('graphTab', 'mermaid')
            ->assertSee('Diagrama Causal de Topologia');
    }

    public function test_approves_and_rejects_proposed_relations_from_detail_view(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $root = $this->makeMemory(['scope' => MemoryScope::GLOBAL]);
        $target = $this->makeMemory(['scope' => MemoryScope::GLOBAL]);

        $projector = app(KnowledgeGraphProjector::class);
        $projector->projectMemory($root);
        $projector->projectMemory($target);

        $nodeA = KnowledgeNode::where('memory_id', $root->id)->first();
        $nodeB = KnowledgeNode::where('memory_id', $target->id)->first();

        $edge = KnowledgeEdge::factory()->create([
            'source_node_id' => $nodeA->id,
            'target_node_id' => $nodeB->id,
            'relation_type' => KnowledgeRelationType::CAUSES,
            'status' => KnowledgeEdgeStatus::PROPOSED,
        ]);

        Livewire::test(MemoryDetail::class, ['memory' => $root])
            ->assertSee('Propostas de Relação Pendentes')
            ->call('approveRelation', $edge->id)
            ->assertDispatched('show-toast', type: 'sucesso');

        $this->assertSame(KnowledgeEdgeStatus::VALIDATED, $edge->fresh()->status);
    }
}
