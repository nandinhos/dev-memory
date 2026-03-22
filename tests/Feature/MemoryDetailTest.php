<?php

namespace Tests\Feature;

use App\Enums\MemoryType;
use App\Enums\MemoryScope;
use App\Enums\ValidationStatus;
use App\Livewire\MemoryDetail;
use App\Models\Memory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MemoryDetailTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function makeMemory(array $attrs = []): Memory
    {
        return Memory::factory()->create(array_merge([
            'type'              => MemoryType::ERROR,
            'scope'             => MemoryScope::PROJECT,
            'validation_status' => ValidationStatus::PENDING,
        ], $attrs));
    }

    public function test_increment_recurrence_dispatches_toast(): void
    {
        $memory = $this->makeMemory();

        Livewire::test(MemoryDetail::class, ['memory' => $memory])
            ->call('incrementRecurrence')
            ->assertDispatched('show-toast');
    }

    public function test_mark_as_validated_dispatches_toast(): void
    {
        $memory = $this->makeMemory(['validation_status' => ValidationStatus::PENDING]);

        Livewire::test(MemoryDetail::class, ['memory' => $memory])
            ->call('markAsValidated')
            ->assertDispatched('show-toast');
    }

    public function test_increment_recurrence_increases_count(): void
    {
        $memory = $this->makeMemory(['recurrence_count' => 1]);

        Livewire::test(MemoryDetail::class, ['memory' => $memory])
            ->call('incrementRecurrence');

        $this->assertEquals(2, $memory->fresh()->recurrence_count);
    }

    public function test_mark_as_validated_updates_status(): void
    {
        $memory = $this->makeMemory(['validation_status' => ValidationStatus::PENDING]);

        Livewire::test(MemoryDetail::class, ['memory' => $memory])
            ->call('markAsValidated');

        $this->assertEquals(
            ValidationStatus::VALIDATED,
            $memory->fresh()->validation_status
        );
    }
}
