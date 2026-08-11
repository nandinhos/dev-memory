<?php

namespace Tests\Feature;

use App\Enums\KnowledgeEdgeOrigin;
use App\Enums\KnowledgeEdgeStatus;
use App\Enums\KnowledgeNodeKind;
use App\Enums\KnowledgeRelationType;
use App\Models\KnowledgeEdge;
use App\Models\KnowledgeNode;
use App\Models\Memory;
use App\Services\KnowledgeGraph\KnowledgeGraphProjector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class KnowledgeGraphProjectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_projects_a_memory_and_its_stack_with_evidence_idempotently(): void
    {
        $memory = Memory::factory()->create([
            'title' => 'Busca híbrida no catálogo',
            'stack' => 'Laravel, PostgreSQL | pgvector',
        ]);

        $projector = app(KnowledgeGraphProjector::class);
        $projector->projectMemory($memory);
        $projector->projectMemory($memory);

        $this->assertDatabaseCount('knowledge_nodes', 4);
        $this->assertDatabaseCount('knowledge_edges', 3);
        $this->assertDatabaseCount('knowledge_edge_evidence', 3);

        $memoryNode = KnowledgeNode::where('memory_id', $memory->id)->firstOrFail();

        $this->assertSame(KnowledgeNodeKind::MEMORY, $memoryNode->kind);
        $this->assertSame($memory->title, $memoryNode->label);
        $this->assertSame($memory->project_id, $memoryNode->properties['project_id']);

        $edge = KnowledgeEdge::with('evidence')->where('source_node_id', $memoryNode->id)->firstOrFail();

        $this->assertSame(KnowledgeRelationType::APPLIES_TO, $edge->relation_type);
        $this->assertSame(KnowledgeEdgeStatus::VALIDATED, $edge->status);
        $this->assertSame(KnowledgeEdgeOrigin::DETERMINISTIC, $edge->origin);
        $this->assertSame($memory->id, $edge->evidence->first()->memory_id);
    }

    public function test_it_removes_stale_deterministic_stack_edges_when_memory_changes(): void
    {
        $memory = Memory::factory()->create(['stack' => 'Laravel, MySQL']);
        $projector = app(KnowledgeGraphProjector::class);

        $projector->projectMemory($memory);
        $memory->update(['stack' => 'Laravel, PostgreSQL']);
        $projector->projectMemory($memory->fresh());

        $labels = KnowledgeNode::query()
            ->whereHas('incomingEdges', fn ($query) => $query->where('origin', KnowledgeEdgeOrigin::DETERMINISTIC->value))
            ->pluck('label');

        $this->assertTrue($labels->contains('Laravel'));
        $this->assertTrue($labels->contains('PostgreSQL'));
        $this->assertFalse($labels->contains('MySQL'));
        $this->assertDatabaseCount('knowledge_edges', 2);
    }

    public function test_ai_extracted_and_sensitive_relations_remain_proposed(): void
    {
        $source = KnowledgeNode::factory()->create(['kind' => KnowledgeNodeKind::CONCEPT]);
        $target = KnowledgeNode::factory()->create(['kind' => KnowledgeNodeKind::CONCEPT]);
        $projector = app(KnowledgeGraphProjector::class);

        $aiEdge = $projector->connect(
            $source,
            $target,
            KnowledgeRelationType::SUPPORTS,
            KnowledgeEdgeStatus::VALIDATED,
            KnowledgeEdgeOrigin::AI_EXTRACTED,
            0.82,
        );

        $contradiction = $projector->connect(
            $target,
            $source,
            KnowledgeRelationType::CONTRADICTS,
            KnowledgeEdgeStatus::VALIDATED,
            KnowledgeEdgeOrigin::DETERMINISTIC,
            0.9,
        );

        $this->assertSame(KnowledgeEdgeStatus::PROPOSED, $aiEdge->status);
        $this->assertSame(KnowledgeEdgeStatus::PROPOSED, $contradiction->status);
    }

    public function test_ai_proposal_cannot_downgrade_an_existing_validated_edge(): void
    {
        $source = KnowledgeNode::factory()->create();
        $target = KnowledgeNode::factory()->create();
        $projector = app(KnowledgeGraphProjector::class);

        $projector->connect(
            $source,
            $target,
            KnowledgeRelationType::SUPPORTS,
            KnowledgeEdgeStatus::VALIDATED,
            KnowledgeEdgeOrigin::HUMAN,
            1,
        );

        $edge = $projector->connect(
            $source,
            $target,
            KnowledgeRelationType::SUPPORTS,
            KnowledgeEdgeStatus::VALIDATED,
            KnowledgeEdgeOrigin::AI_EXTRACTED,
            0.7,
            evidence: ['source_type' => 'ai_extraction', 'excerpt' => 'Evidência adicional'],
        );

        $this->assertSame(KnowledgeEdgeStatus::VALIDATED, $edge->status);
        $this->assertSame(KnowledgeEdgeOrigin::HUMAN, $edge->origin);
        $this->assertSame(1.0, $edge->confidence);
        $this->assertCount(1, $edge->evidence);
    }

    public function test_it_rejects_self_edges(): void
    {
        $node = KnowledgeNode::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        app(KnowledgeGraphProjector::class)->connect(
            $node,
            $node,
            KnowledgeRelationType::SUPPORTS,
            KnowledgeEdgeStatus::VALIDATED,
            KnowledgeEdgeOrigin::HUMAN,
            1,
        );
    }
}
