<?php

namespace App\Services;

use App\Enums\MemoryScope;
use App\Enums\ValidationStatus;
use App\Models\Memory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class MemoryService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Memory::query()
            ->filter($filters)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function search(string $query): Collection
    {
        return Memory::query()
            ->where(fn ($q) => $q->where('title', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
            )
            ->orderBy('recurrence_count', 'desc')
            ->limit(20)
            ->get();
    }

    public function findById(string $id): ?Memory
    {
        return Memory::findOrFail($id);
    }

    public function create(array $data): Memory
    {
        return Memory::create($data);
    }

    public function update(Memory $memory, array $data): Memory
    {
        $memory->update($data);

        return $memory->fresh();
    }

    public function delete(Memory $memory): void
    {
        $memory->delete();
    }

    public function incrementRecurrence(Memory $memory): Memory
    {
        $memory->increment('recurrence_count');

        return $memory->fresh();
    }

    public function validate(Memory $memory): Memory
    {
        $memory->update(['validation_status' => ValidationStatus::VALIDATED]);

        return $memory->fresh();
    }

    public function reject(Memory $memory): Memory
    {
        $memory->update(['validation_status' => ValidationStatus::REJECTED]);

        return $memory->fresh();
    }

    public function promoteToGlobal(Memory $memory): Memory
    {
        if ($memory->validation_status !== ValidationStatus::VALIDATED) {
            throw new \InvalidArgumentException('Memory must be validated before promoting to global');
        }

        $memory->update(['scope' => MemoryScope::GLOBAL]);

        return $memory->fresh();
    }

    public function getStats(): array
    {
        return [
            'total' => Memory::count(),
            'by_type' => [
                'error' => Memory::errors()->count(),
                'lesson' => Memory::lessons()->count(),
                'best_practice' => Memory::bestPractices()->count(),
            ],
            'by_scope' => [
                'project' => Memory::project()->count(),
                'global' => Memory::global()->count(),
            ],
            'by_validation' => [
                'pending' => Memory::where('validation_status', ValidationStatus::PENDING)->count(),
                'validated' => Memory::validated()->count(),
            ],
            'top_stacks' => Memory::selectRaw('stack, COUNT(*) as count')
                ->whereNotNull('stack')
                ->groupBy('stack')
                ->orderByDesc('count')
                ->limit(5)
                ->get(),
        ];
    }
}
