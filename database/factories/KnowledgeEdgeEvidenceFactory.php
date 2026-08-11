<?php

namespace Database\Factories;

use App\Models\KnowledgeEdge;
use App\Models\KnowledgeEdgeEvidence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeEdgeEvidence>
 */
class KnowledgeEdgeEvidenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'knowledge_edge_id' => KnowledgeEdge::factory(),
            'source_type' => 'test',
            'excerpt' => $this->faker->sentence(),
            'evidence_hash' => hash('sha256', $this->faker->unique()->sentence()),
            'confidence' => $this->faker->randomFloat(4, 0.5, 1),
            'metadata' => [],
        ];
    }
}
