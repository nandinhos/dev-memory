<?php

namespace Tests\Unit;

use App\Enums\MemoryScope;
use App\Enums\MemoryType;
use App\Enums\ValidationStatus;
use App\Models\Memory;
use App\Services\MemoryService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;
use Tests\TestCase;

class MemoryServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private MemoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MemoryService;
    }

    public function test_list_returns_paginated_memories(): void
    {
        Memory::factory()->count(25)->create();

        $result = $this->service->list([], 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(10, $result->perPage());
        $this->assertEquals(25, $result->total());
    }

    public function test_list_filters_by_type(): void
    {
        Memory::factory()->error()->count(3)->create();
        Memory::factory()->lesson()->count(2)->create();

        $result = $this->service->list(['type' => 'error']);

        $this->assertEquals(3, $result->total());
    }

    public function test_list_filters_by_scope(): void
    {
        Memory::factory()->count(2)->create(['scope' => MemoryScope::PROJECT]);
        Memory::factory()->global()->create();

        $result = $this->service->list(['scope' => 'global']);

        $this->assertEquals(1, $result->total());
    }

    public function test_list_filters_by_stack(): void
    {
        Memory::factory()->create(['stack' => 'Laravel']);
        Memory::factory()->create(['stack' => 'Vue']);
        Memory::factory()->create(['stack' => 'Laravel Livewire']);

        $result = $this->service->list(['stack' => 'Laravel']);

        $this->assertEquals(2, $result->total());
    }

    public function test_list_filters_by_search(): void
    {
        Memory::factory()->create(['title' => 'Laravel Error', 'description' => 'Some error']);
        Memory::factory()->create(['title' => 'Vue Lesson', 'description' => 'Vue tutorial']);

        $result = $this->service->list(['search' => 'Laravel']);

        $this->assertEquals(1, $result->total());
    }

    public function test_search_returns_collection(): void
    {
        Memory::factory()->count(5)->create();

        $result = $this->service->search('test');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertLessThanOrEqual(20, $result->count());
    }

    public function test_search_orders_by_recurrence(): void
    {
        $low = Memory::factory()->create(['title' => 'Test', 'recurrence_count' => 1]);
        $high = Memory::factory()->create(['title' => 'Test', 'recurrence_count' => 10]);

        $result = $this->service->search('Test');

        $this->assertEquals($high->id, $result->first()->id);
    }

    public function test_find_by_id_returns_memory(): void
    {
        $memory = Memory::factory()->create();

        $result = $this->service->findById($memory->id);

        $this->assertEquals($memory->id, $result->id);
    }

    public function test_create_memory(): void
    {
        $data = [
            'title' => 'Test Memory',
            'description' => 'Test description',
            'type' => 'error',
            'stack' => 'Laravel',
        ];

        $result = $this->service->create($data);

        $this->assertEquals('Test Memory', $result->title);
        $this->assertDatabaseHas('memories', ['title' => 'Test Memory']);
    }

    public function test_update_memory(): void
    {
        $memory = Memory::factory()->create();

        $result = $this->service->update($memory, ['title' => 'Updated']);

        $this->assertEquals('Updated', $result->title);
    }

    public function test_delete_memory(): void
    {
        $memory = Memory::factory()->create();

        $this->service->delete($memory);

        $this->assertDatabaseMissing('memories', ['id' => $memory->id]);
    }

    public function test_increment_recurrence(): void
    {
        $memory = Memory::factory()->create(['recurrence_count' => 5]);

        $result = $this->service->incrementRecurrence($memory);

        $this->assertEquals(6, $result->recurrence_count);
    }

    public function test_validate_memory(): void
    {
        $memory = Memory::factory()->create(['validation_status' => ValidationStatus::PENDING]);

        $result = $this->service->validate($memory);

        $this->assertEquals(ValidationStatus::VALIDATED, $result->validation_status);
    }

    public function test_reject_memory(): void
    {
        $memory = Memory::factory()->create(['validation_status' => ValidationStatus::PENDING]);

        $result = $this->service->reject($memory);

        $this->assertEquals(ValidationStatus::REJECTED, $result->validation_status);
    }

    public function test_promote_to_global_requires_validation(): void
    {
        $memory = Memory::factory()->create([
            'validation_status' => ValidationStatus::PENDING,
        ]);

        $this->expectException(InvalidArgumentException::class);

        $this->service->promoteToGlobal($memory);
    }

    public function test_promote_to_global(): void
    {
        $memory = Memory::factory()->create([
            'scope' => MemoryScope::PROJECT,
            'validation_status' => ValidationStatus::VALIDATED,
        ]);

        $result = $this->service->promoteToGlobal($memory);

        $this->assertEquals(MemoryScope::GLOBAL, $result->scope);
    }

    public function test_get_stats(): void
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

        $stats = $this->service->getStats();

        $this->assertEquals(11, $stats['total']);
        $this->assertEquals(2, $stats['by_type']['error']);
        $this->assertEquals(3, $stats['by_type']['lesson']);
        $this->assertEquals(6, $stats['by_type']['best_practice']);
        $this->assertEquals(1, $stats['by_scope']['global']);
        $this->assertEquals(4, $stats['by_validation']['validated']);
        $this->assertArrayHasKey('top_stacks', $stats);
    }
}
