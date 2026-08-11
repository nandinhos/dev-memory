<?php

namespace Database\Factories;

use App\Enums\KnowledgeEdgeOrigin;
use App\Enums\KnowledgeEdgeStatus;
use App\Enums\KnowledgeRelationType;
use App\Models\KnowledgeEdge;
use App\Models\KnowledgeNode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeEdge>
 */
class KnowledgeEdgeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_node_id' => KnowledgeNode::factory(),
            'target_node_id' => KnowledgeNode::factory(),
            'relation_type' => KnowledgeRelationType::SUPPORTS,
            'status' => KnowledgeEdgeStatus::PROPOSED,
            'confidence' => $this->faker->randomFloat(4, 0.5, 1),
            'origin' => KnowledgeEdgeOrigin::HUMAN,
            'properties' => [],
        ];
    }
}
