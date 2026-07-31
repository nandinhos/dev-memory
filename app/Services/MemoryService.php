<?php

namespace App\Services;

use App\Enums\MemoryMaturity;
use App\Enums\MemoryScope;
use App\Enums\ValidationStatus;
use App\Jobs\GenerateMemoryEmbeddingJob;
use App\Models\Memory;
use App\Models\User;
use App\Services\Search\MemorySearchService;
use Illuminate\Pagination\LengthAwarePaginator;

class MemoryService
{
    public function __construct(
        private MemorySearchService $searchService,
        private MaturityPolicy $maturityPolicy,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Memory::query()
            ->filter($filters);

        if (! empty($filters['maturity'])) {
            $query->where('maturity', $filters['maturity']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function search(string $query, array $filters = []): array
    {
        return $this->searchService->search($query, $filters);
    }

    public function findById(string $id): ?Memory
    {
        return Memory::findOrFail($id);
    }

    public function create(array $data): Memory
    {
        $memory = Memory::create($data);
        GenerateMemoryEmbeddingJob::dispatch($memory);

        return $memory;
    }

    public function update(Memory $memory, array $data): Memory
    {
        $memory->update($data);
        GenerateMemoryEmbeddingJob::dispatch($memory);

        return $memory->fresh();
    }

    public function promoteMaturity(Memory $memory, MemoryMaturity $target, ?User $user = null): Memory
    {
        return $this->maturityPolicy->transition($memory, $target, $user);
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

    /**
     * Find an existing memory with the same (or nearly the same) title.
     * Deterministic dedup for the capture pipeline: normalized equality
     * or Levenshtein distance <= 3. Semantic dedup arrives in P5.
     */
    public function findSimilarByTitle(string $title): ?Memory
    {
        $normalized = $this->normalizeTitle($title);

        return Memory::query()
            ->get(['id', 'title'])
            ->first(function (Memory $memory) use ($normalized) {
                $candidate = $this->normalizeTitle($memory->title);

                return $candidate === $normalized
                    || (mb_strlen($candidate) < 200 && levenshtein($candidate, $normalized) <= 3);
            })
            ?->fresh();
    }

    private function normalizeTitle(string $title): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $title)));
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
