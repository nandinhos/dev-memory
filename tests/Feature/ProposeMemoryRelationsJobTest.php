<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\KnowledgeEdgeOrigin;
use App\Enums\KnowledgeEdgeStatus;
use App\Enums\KnowledgeRelationType;
use App\Enums\MemoryScope;
use App\Enums\MemoryType;
use App\Enums\ValidationStatus;
use App\Jobs\ProposeMemoryRelationsJob;
use App\Models\KnowledgeEdge;
use App\Models\Memory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProposeMemoryRelationsJobTest extends TestCase
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

    public function test_it_finds_candidates_and_proposes_ai_relations(): void
    {
        $response = [
            'relation' => 'resolves',
            'confidence' => 0.90,
            'rationale' => 'A lição propõe o uso de transactions para evitar deadlocks.',
            'evidence_excerpt' => 'Usar select for update ordenado',
        ];

        Http::fake([
            'https://fake.minimax.test/anthropic/*' => Http::response([
                'content' => [['type' => 'text', 'text' => json_encode($response)]],
                'usage' => ['input_tokens' => 200, 'output_tokens' => 80],
            ], 200),
        ]);

        $error = Memory::factory()->create([
            'title' => 'Deadlock de transação no Postgres',
            'type' => MemoryType::ERROR,
            'stack' => 'PostgreSQL, Laravel',
            'scope' => MemoryScope::GLOBAL,
            'validation_status' => ValidationStatus::VALIDATED,
        ]);

        $solution = Memory::factory()->create([
            'title' => 'Usar select for update com ordenação consistente',
            'type' => MemoryType::BEST_PRACTICE,
            'stack' => 'PostgreSQL, Laravel',
            'scope' => MemoryScope::GLOBAL,
            'validation_status' => ValidationStatus::VALIDATED,
        ]);

        ProposeMemoryRelationsJob::dispatchSync($error->id);

        $this->assertDatabaseHas('knowledge_edges', [
            'relation_type' => KnowledgeRelationType::RESOLVES->value,
            'status' => KnowledgeEdgeStatus::PROPOSED->value,
            'origin' => KnowledgeEdgeOrigin::AI_EXTRACTED->value,
        ]);

        $edge = KnowledgeEdge::where('relation_type', KnowledgeRelationType::RESOLVES)->firstOrFail();
        $this->assertEqualsWithDelta(0.90, $edge->confidence, 0.01);
    }
}
