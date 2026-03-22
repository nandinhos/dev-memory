<?php

namespace Database\Factories;

use App\Enums\MemoryScope;
use App\Enums\MemoryType;
use App\Enums\ValidationStatus;
use App\Models\Memory;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemoryFactory extends Factory
{
    protected $model = Memory::class;

    public function definition(): array
    {
        $types = [MemoryType::ERROR, MemoryType::LESSON, MemoryType::BEST_PRACTICE];
        $scopes = [MemoryScope::PROJECT, MemoryScope::GLOBAL];
        $statuses = [ValidationStatus::PENDING, ValidationStatus::VALIDATED, ValidationStatus::REJECTED];

        return [
            'project_id' => $this->faker->optional()->uuid(),
            'title' => $this->faker->sentence(6),
            'description' => $this->faker->paragraphs(2, true),
            'type' => $this->faker->randomElement($types),
            'stack' => $this->faker->randomElement(['Laravel', 'Livewire', 'PHP', 'Docker', 'Tailwind', 'Alpine.js', 'PostgreSQL']),
            'scope' => $this->faker->randomElement($scopes),
            'validation_status' => $this->faker->randomElement($statuses),
            'official_reference' => $this->faker->optional()->url(),
            'recurrence_count' => $this->faker->numberBetween(1, 50),
        ];
    }

    public function error(): static
    {
        return $this->state(fn (array $attributes) => ['type' => MemoryType::ERROR]);
    }

    public function lesson(): static
    {
        return $this->state(fn (array $attributes) => ['type' => MemoryType::LESSON]);
    }

    public function bestPractice(): static
    {
        return $this->state(fn (array $attributes) => ['type' => MemoryType::BEST_PRACTICE]);
    }

    public function global(): static
    {
        return $this->state(fn (array $attributes) => ['scope' => MemoryScope::GLOBAL]);
    }

    public function validated(): static
    {
        return $this->state(fn (array $attributes) => ['validation_status' => ValidationStatus::VALIDATED]);
    }
}
