<?php

namespace Tests\Feature;

use App\Enums\KnowledgeEdgeStatus;
use App\Enums\KnowledgeRelationType;
use App\Enums\MemoryScope;
use App\Enums\MemoryType;
use App\Enums\ValidationStatus;
use App\Livewire\KnowledgeGraphExplorer;
use App\Models\KnowledgeEdge;
use App\Models\KnowledgeNode;
use App\Models\Memory;
use App\Models\User;
use App\Services\KnowledgeGraph\KnowledgeGraphProjector;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KnowledgeGraphExplorerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function makeMemory(array $attrs = []): Memory
    {
        return Memory::factory()->create(array_merge([
            'type' => MemoryType::ERROR,
            'scope' => MemoryScope::GLOBAL,
            'validation_status' => ValidationStatus::VALIDATED,
        ], $attrs));
    }

    public function test_requires_authentication_to_access_graph_route(): void
    {
        $response = $this->get(route('graph.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_graph_route(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('graph.index'));
        $response->assertOk()
            ->assertSee('Grafo Causal');
    }

    public function test_renders_nodes_and_edges_in_overview_mode(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $memA = $this->makeMemory(['title' => 'Problema A']);
        $memB = $this->makeMemory(['title' => 'Solucao B', 'type' => MemoryType::BEST_PRACTICE]);

        $projector = app(KnowledgeGraphProjector::class);
        $projector->projectMemory($memA);
        $projector->projectMemory($memB);

        $nodeA = KnowledgeNode::where('memory_id', $memA->id)->firstOrFail();
        $nodeB = KnowledgeNode::where('memory_id', $memB->id)->firstOrFail();

        KnowledgeEdge::factory()->create([
            'source_node_id' => $nodeA->id,
            'target_node_id' => $nodeB->id,
            'relation_type' => KnowledgeRelationType::RESOLVES,
            'status' => KnowledgeEdgeStatus::VALIDATED,
        ]);

        Livewire::test(KnowledgeGraphExplorer::class)
            ->assertSee('Problema A')
            ->assertSee('Solucao B')
            ->assertSee('resolves');
    }

    public function test_renders_focused_graph_data_with_two_depth_limit(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $root = $this->makeMemory(['title' => 'Vite Out of Memory']);
        $neighbor1 = $this->makeMemory(['title' => 'Aumentar Node Heap', 'type' => MemoryType::BEST_PRACTICE]);
        $neighbor2 = $this->makeMemory(['title' => 'Configurar Swap', 'type' => MemoryType::LESSON]);

        $projector = app(KnowledgeGraphProjector::class);
        $projector->projectMemory($root);
        $projector->projectMemory($neighbor1);
        $projector->projectMemory($neighbor2);

        $nodeRoot = KnowledgeNode::where('memory_id', $root->id)->firstOrFail();
        $nodeN1 = KnowledgeNode::where('memory_id', $neighbor1->id)->firstOrFail();
        $nodeN2 = KnowledgeNode::where('memory_id', $neighbor2->id)->firstOrFail();

        KnowledgeEdge::factory()->create([
            'source_node_id' => $nodeRoot->id,
            'target_node_id' => $nodeN1->id,
            'relation_type' => KnowledgeRelationType::RESOLVES,
            'status' => KnowledgeEdgeStatus::VALIDATED,
        ]);

        KnowledgeEdge::factory()->create([
            'source_node_id' => $nodeN1->id,
            'target_node_id' => $nodeN2->id,
            'relation_type' => KnowledgeRelationType::SUPPORTS,
            'status' => KnowledgeEdgeStatus::VALIDATED,
        ]);

        Livewire::withQueryParams(['focus' => $root->id])
            ->test(KnowledgeGraphExplorer::class)
            ->assertSee('FOCO ATIVO (2 SALTOS)')
            ->assertSee('Vite Out of Memory')
            ->assertSee('Aumentar Node Heap')
            ->assertSet('focus', $root->id);
    }

    public function test_can_select_and_clear_node_selection(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $mem = $this->makeMemory(['title' => 'Erro EADDRINUSE na porta 3000']);

        Livewire::test(KnowledgeGraphExplorer::class)
            ->call('selectNode', $mem->id)
            ->assertSet('selectedMemoryId', $mem->id)
            ->assertSee('Erro EADDRINUSE na porta 3000')
            ->call('clearSelection')
            ->assertSet('selectedMemoryId', null);
    }

    public function test_can_set_and_clear_focus(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $mem = $this->makeMemory(['title' => 'Memoria Central']);

        Livewire::test(KnowledgeGraphExplorer::class)
            ->call('setFocus', $mem->id)
            ->assertSet('focus', $mem->id)
            ->assertDispatched('graph-data-updated')
            ->call('clearFocus')
            ->assertSet('focus', null)
            ->assertDispatched('graph-data-updated');
    }
}
