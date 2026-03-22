<?php

namespace Tests\Feature;

use App\Enums\MemoryScope;
use App\Enums\MemoryType;
use App\Enums\ValidationStatus;
use App\Models\Memory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class MemoryApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_can_list_memories(): void
    {
        Memory::factory()->count(3)->create();

        $response = $this->getJson('/api/memories');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_memory(): void
    {
        $data = [
            'title' => 'Test Memory',
            'description' => 'Test description',
            'type' => 'error',
            'stack' => 'Laravel',
            'scope' => 'project',
        ];

        $response = $this->postJson('/api/memories', $data);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Test Memory')
            ->assertJsonPath('data.type', 'error');

        $this->assertDatabaseHas('memories', [
            'title' => 'Test Memory',
            'type' => 'error',
        ]);
    }

    public function test_can_show_memory(): void
    {
        $memory = Memory::factory()->create();

        $response = $this->getJson("/api/memories/{$memory->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $memory->id);
    }

    public function test_can_update_memory(): void
    {
        $memory = Memory::factory()->create();

        $response = $this->putJson("/api/memories/{$memory->id}", [
            'title' => 'Updated Title',
            'description' => $memory->description,
            'type' => $memory->type->value,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated Title');
    }

    public function test_can_delete_memory(): void
    {
        $memory = Memory::factory()->create();

        $response = $this->deleteJson("/api/memories/{$memory->id}");

        $response->assertOk();
        $this->assertSoftDeleted('memories', ['id' => $memory->id]);
    }

    public function test_can_search_memories(): void
    {
        Memory::factory()->create(['title' => 'Laravel Error', 'description' => 'Test']);
        Memory::factory()->create(['title' => 'Vue Lesson', 'description' => 'Test']);

        $response = $this->getJson('/api/memories/search?q=Laravel');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laravel Error');
    }

    public function test_can_filter_by_type(): void
    {
        Memory::factory()->create(['type' => MemoryType::ERROR]);
        Memory::factory()->create(['type' => MemoryType::ERROR]);
        Memory::factory()->create(['type' => MemoryType::LESSON]);
        Memory::factory()->create(['type' => MemoryType::LESSON]);
        Memory::factory()->create(['type' => MemoryType::LESSON]);

        $response = $this->getJson('/api/memories?type=error');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_by_scope(): void
    {
        Memory::factory()->create(['scope' => MemoryScope::PROJECT]);
        Memory::factory()->create(['scope' => MemoryScope::GLOBAL]);

        $response = $this->getJson('/api/memories?scope=global');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.scope', 'global');
    }

    public function test_can_validate_memory(): void
    {
        $memory = Memory::factory()->create(['validation_status' => ValidationStatus::PENDING]);

        $response = $this->postJson("/api/memories/{$memory->id}/validate");

        $response->assertOk()
            ->assertJsonPath('data.validation_status', 'validated');

        $this->assertDatabaseHas('memories', [
            'id' => $memory->id,
            'validation_status' => 'validated',
        ]);
    }

    public function test_can_promote_to_global(): void
    {
        $memory = Memory::factory()->create([
            'validation_status' => ValidationStatus::VALIDATED,
            'scope' => MemoryScope::PROJECT,
        ]);

        $response = $this->postJson("/api/memories/{$memory->id}/promote");

        $response->assertOk();
        $this->assertDatabaseHas('memories', [
            'id' => $memory->id,
            'scope' => 'global',
        ]);
    }

    public function test_promote_requires_validation(): void
    {
        $memory = Memory::factory()->create([
            'validation_status' => ValidationStatus::PENDING,
            'scope' => MemoryScope::PROJECT,
        ]);

        $response = $this->postJson("/api/memories/{$memory->id}/promote");

        $response->assertUnprocessable();
    }

    public function test_can_get_stats(): void
    {
        Memory::factory()->create([
            'type' => MemoryType::ERROR,
            'validation_status' => ValidationStatus::PENDING,
            'scope' => MemoryScope::PROJECT,
        ]);
        Memory::factory()->create([
            'type' => MemoryType::ERROR,
            'validation_status' => ValidationStatus::PENDING,
            'scope' => MemoryScope::PROJECT,
        ]);
        Memory::factory()->create([
            'type' => MemoryType::LESSON,
            'validation_status' => ValidationStatus::PENDING,
            'scope' => MemoryScope::PROJECT,
        ]);
        Memory::factory()->create([
            'type' => MemoryType::LESSON,
            'validation_status' => ValidationStatus::PENDING,
            'scope' => MemoryScope::PROJECT,
        ]);
        Memory::factory()->create([
            'type' => MemoryType::LESSON,
            'validation_status' => ValidationStatus::PENDING,
            'scope' => MemoryScope::PROJECT,
        ]);
        Memory::factory()->create([
            'type' => MemoryType::BEST_PRACTICE,
            'validation_status' => ValidationStatus::PENDING,
            'scope' => MemoryScope::PROJECT,
        ]);
        Memory::factory()->create([
            'type' => MemoryType::BEST_PRACTICE,
            'validation_status' => ValidationStatus::PENDING,
            'scope' => MemoryScope::GLOBAL,
        ]);
        Memory::factory()->create([
            'type' => MemoryType::BEST_PRACTICE,
            'validation_status' => ValidationStatus::VALIDATED,
            'scope' => MemoryScope::PROJECT,
        ]);
        Memory::factory()->create([
            'type' => MemoryType::BEST_PRACTICE,
            'validation_status' => ValidationStatus::VALIDATED,
            'scope' => MemoryScope::PROJECT,
        ]);
        Memory::factory()->create([
            'type' => MemoryType::BEST_PRACTICE,
            'validation_status' => ValidationStatus::VALIDATED,
            'scope' => MemoryScope::PROJECT,
        ]);
        Memory::factory()->create([
            'type' => MemoryType::BEST_PRACTICE,
            'validation_status' => ValidationStatus::VALIDATED,
            'scope' => MemoryScope::PROJECT,
        ]);

        $response = $this->getJson('/api/stats');

        $response->assertOk()
            ->assertJsonPath('total', 11)
            ->assertJsonPath('by_type.error', 2)
            ->assertJsonPath('by_type.lesson', 3)
            ->assertJsonPath('by_scope.global', 1)
            ->assertJsonPath('by_validation.validated', 4);
    }

    public function test_create_requires_title(): void
    {
        $response = $this->postJson('/api/memories', [
            'description' => 'Test',
            'type' => 'error',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_create_requires_valid_type(): void
    {
        $response = $this->postJson('/api/memories', [
            'title' => 'Test',
            'description' => 'Test',
            'type' => 'invalid_type',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }
}
