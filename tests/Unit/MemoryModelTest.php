<?php

namespace Tests\Unit;

use App\Enums\MemoryScope;
use App\Enums\MemoryType;
use App\Enums\ValidationStatus;
use App\Models\Memory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemoryModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_filter_by_type(): void
    {
        Memory::factory()->error()->count(2)->create();
        Memory::factory()->lesson()->count(3)->create();

        $errors = Memory::filter(['type' => 'error'])->get();
        $lessons = Memory::filter(['type' => 'lesson'])->get();

        $this->assertCount(2, $errors);
        $this->assertCount(3, $lessons);
    }

    public function test_filter_by_scope(): void
    {
        Memory::factory()->count(2)->create(['scope' => MemoryScope::PROJECT]);
        Memory::factory()->global()->count(3)->create();

        $global = Memory::filter(['scope' => 'global'])->get();

        $this->assertCount(3, $global);
    }

    public function test_filter_by_stack(): void
    {
        Memory::factory()->create(['stack' => 'Laravel']);
        Memory::factory()->create(['stack' => 'Laravel Livewire']);
        Memory::factory()->create(['stack' => 'Vue']);

        $result = Memory::filter(['stack' => 'Laravel'])->get();

        $this->assertCount(2, $result);
    }

    public function test_filter_by_search(): void
    {
        Memory::factory()->create(['title' => 'Laravel Error Handling']);
        Memory::factory()->create(['title' => 'Vue Components']);
        Memory::factory()->create(['description' => 'Laravel best practices']);

        $result = Memory::filter(['search' => 'Laravel'])->get();

        $this->assertCount(2, $result);
    }

    public function test_scope_errors(): void
    {
        Memory::factory()->error()->count(3)->create();
        Memory::factory()->lesson()->count(2)->create();

        $errors = Memory::errors()->get();

        $this->assertCount(3, $errors);
    }

    public function test_scope_lessons(): void
    {
        Memory::factory()->error()->count(2)->create();
        Memory::factory()->lesson()->count(4)->create();

        $lessons = Memory::lessons()->get();

        $this->assertCount(4, $lessons);
    }

    public function test_scope_best_practices(): void
    {
        Memory::factory()->bestPractice()->count(3)->create();
        Memory::factory()->error()->count(2)->create();

        $practices = Memory::bestPractices()->get();

        $this->assertCount(3, $practices);
    }

    public function test_scope_global(): void
    {
        Memory::factory()->global()->count(2)->create();
        Memory::factory()->count(3)->create(['scope' => MemoryScope::PROJECT]);

        $global = Memory::global()->get();

        $this->assertCount(2, $global);
    }

    public function test_scope_project(): void
    {
        Memory::factory()->global()->count(1)->create();
        Memory::factory()->count(4)->create(['scope' => MemoryScope::PROJECT]);

        $project = Memory::project()->get();

        $this->assertCount(4, $project);
    }

    public function test_scope_validated(): void
    {
        Memory::factory()->validated()->count(3)->create();
        Memory::factory()->count(2)->create(['validation_status' => ValidationStatus::PENDING]);

        $validated = Memory::validated()->get();

        $this->assertCount(3, $validated);
    }

    public function test_type_casts_to_enum(): void
    {
        $memory = Memory::factory()->create(['type' => MemoryType::ERROR]);

        $this->assertInstanceOf(MemoryType::class, $memory->type);
        $this->assertEquals(MemoryType::ERROR, $memory->type);
    }

    public function test_scope_casts_to_enum(): void
    {
        $memory = Memory::factory()->global()->create();

        $this->assertInstanceOf(MemoryScope::class, $memory->scope);
        $this->assertEquals(MemoryScope::GLOBAL, $memory->scope);
    }

    public function test_validation_status_casts_to_enum(): void
    {
        $memory = Memory::factory()->validated()->create();

        $this->assertInstanceOf(ValidationStatus::class, $memory->validation_status);
        $this->assertEquals(ValidationStatus::VALIDATED, $memory->validation_status);
    }

    public function test_uses_uuid_as_primary_key(): void
    {
        $memory = Memory::factory()->create();

        $this->assertIsString($memory->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $memory->id
        );
    }
}
