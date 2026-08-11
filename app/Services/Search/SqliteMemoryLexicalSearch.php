<?php

namespace App\Services\Search;

use App\Contracts\MemoryLexicalSearch;
use App\Models\Memory;
use Illuminate\Support\Collection;

class SqliteMemoryLexicalSearch implements MemoryLexicalSearch
{
    public function search(string $query, int $limit = 10, array $filters = []): Collection
    {
        $needle = mb_strtolower(trim($query));

        return Memory::query()
            ->filter(array_merge($filters, ['search' => $query]))
            ->when(
                $filters['maturity'] ?? null,
                fn ($builder, $maturity) => $builder->where('maturity', $maturity),
            )
            ->limit($limit)
            ->get()
            ->map(function (Memory $memory) use ($needle) {
                $title = mb_strtolower($memory->title);
                $description = mb_strtolower($memory->description);

                $score = match (true) {
                    $title === $needle => 3.0,
                    str_contains($title, $needle) => 2.0,
                    str_contains($description, $needle) => 1.0,
                    default => 0.0,
                };

                return ['memory' => $memory, 'lexical_score' => $score];
            })
            ->sortByDesc('lexical_score')
            ->values();
    }
}
