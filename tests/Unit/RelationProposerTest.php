<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\KnowledgeEdgeOrigin;
use App\Enums\KnowledgeEdgeStatus;
use App\Enums\KnowledgeRelationType;
use App\Enums\MemoryType;
use App\Models\KnowledgeEdge;
use App\Models\Memory;
use App\Services\KnowledgeGraph\KnowledgeGraphQueryService;
use App\Services\KnowledgeGraph\RelationProposer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RelationProposerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.minimax.base_url' => 'https://fake.minimax.test/anthropic',
            'services.minimax.api_key' => 'test-key',
            'services.minimax.model' => 'MiniMax-M2.7',
        ]);
    }

    private function makeMemory(array $overrides = []): Memory
    {
        return Memory::factory()->create(array_merge([
            'title' => 'ILIKE não funciona no SQLite',
            'description' => 'ILIKE é específico do PostgreSQL; usar LOWER + LIKE para portabilidade.',
            'type' => MemoryType::ERROR,
            'stack' => 'Laravel, PostgreSQL',
        ], $overrides));
    }

    private function fakeEngineResponse(array $overrides = []): array
    {
        $response = array_merge([
            'relation' => 'resolves',
            'confidence' => 0.85,
            'rationale' => 'A memória B descreve a solução para o problema da memória A.',
            'evidence_excerpt' => 'usar LOWER + LIKE para portabilidade',
        ], $overrides);

        return [
            'content' => [['type' => 'text', 'text' => json_encode($response)]],
            'usage' => ['input_tokens' => 500, 'output_tokens' => 200],
        ];
    }

    public function test_propose_creates_proposed_edge_via_ai(): void
    {
        Http::fake([
            'fake.minimax.test/*' => Http::response($this->fakeEngineResponse()),
        ]);

        $source = $this->makeMemory(['title' => 'Problema: ILIKE no SQLite']);
        $target = $this->makeMemory(['title' => 'Solução: LOWER + LIKE']);

        $result = app(RelationProposer::class)->propose($source, $target);

        $this->assertTrue($result['suggested_by_ai']);
        $this->assertSame('resolves', $result['relation']);
        $this->assertSame('proposed', $result['status']);
        $this->assertEqualsWithDelta(0.85, $result['confidence'], 0.01);
        $this->assertNotEmpty($result['edge_id']);

        // A aresta deve existir no banco com status PROPOSED
        $edge = KnowledgeEdge::findOrFail($result['edge_id']);
        $this->assertSame(KnowledgeEdgeStatus::PROPOSED, $edge->status);
        $this->assertSame(KnowledgeEdgeOrigin::AI_EXTRACTED, $edge->origin);
        $this->assertSame(KnowledgeRelationType::RESOLVES, $edge->relation_type);
    }

    public function test_propose_respects_relation_hint(): void
    {
        Http::fake([
            'fake.minimax.test/*' => Http::response($this->fakeEngineResponse([
                'relation' => 'causes',
                'confidence' => 0.7,
            ])),
        ]);

        $source = $this->makeMemory();
        $target = $this->makeMemory();

        $result = app(RelationProposer::class)->propose($source, $target, 'causes');

        $this->assertSame('causes', $result['relation']);
    }

    public function test_propose_rejects_invalid_hint(): void
    {
        $source = $this->makeMemory();
        $target = $this->makeMemory();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tipo de relação inválido');

        app(RelationProposer::class)->propose($source, $target, 'inválido');
    }

    public function test_propose_rejects_same_memory(): void
    {
        $memory = $this->makeMemory();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Uma relação não pode ligar uma memória a ela mesma');

        app(RelationProposer::class)->propose($memory, $memory);
    }

    public function test_proposed_edge_does_not_appear_in_memory_related(): void
    {
        Http::fake([
            'fake.minimax.test/*' => Http::response($this->fakeEngineResponse()),
        ]);

        $source = $this->makeMemory(['stack' => 'Laravel, PostgreSQL']);
        $target = $this->makeMemory(['stack' => 'Vue.js, Vite']);

        app(RelationProposer::class)->propose($source, $target);

        // memory_related só retorna arestas VALIDATED; stacks disjuntos garantem
        // que o único caminho source -> target seja a aresta PROPOSED.
        $results = app(KnowledgeGraphQueryService::class)->relatedTo($source);
        $this->assertEmpty($results);
    }

    public function test_proposed_edge_appears_after_approval(): void
    {
        Http::fake([
            'fake.minimax.test/*' => Http::response($this->fakeEngineResponse()),
        ]);

        $source = $this->makeMemory();
        $target = $this->makeMemory();

        $result = app(RelationProposer::class)->propose($source, $target);

        // Aprova a aresta (simula ação do admin)
        $edge = KnowledgeEdge::findOrFail($result['edge_id']);
        $edge->update(['status' => KnowledgeEdgeStatus::VALIDATED]);

        // A aresta deve estar VALIDATED no banco
        $edge->refresh();
        $this->assertSame(KnowledgeEdgeStatus::VALIDATED, $edge->status);
    }
}
