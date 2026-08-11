<?php

namespace Database\Factories;

use App\Enums\KnowledgeNodeKind;
use App\Models\KnowledgeNode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeNode>
 */
class KnowledgeNodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kind' => KnowledgeNodeKind::CONCEPT,
            'namespace' => 'concept',
            'canonical_key' => hash('sha256', $this->faker->unique()->words(3, true)),
            'label' => $this->faker->words(3, true),
            'properties' => [],
            'status' => 'active',
        ];
    }
}
