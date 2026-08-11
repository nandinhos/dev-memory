<?php

namespace App\Services\Search;

use App\Contracts\MemoryLexicalSearch;
use App\Models\Memory;
use Illuminate\Support\Collection;

class PgsqlMemoryLexicalSearch implements MemoryLexicalSearch
{
    public function search(string $query, int $limit = 10, array $filters = []): Collection
    {
        $tsQuery = "websearch_to_tsquery('simple', ?)";

        $memories = Memory::query()
            ->select('*')
            ->selectRaw("ts_rank_cd(search_vector, {$tsQuery}) AS lexical_score", [$query])
            ->whereRaw("search_vector @@ {$tsQuery}", [$query])
            ->filter($filters)
            ->when(
                $filters['maturity'] ?? null,
                fn ($builder, $maturity) => $builder->where('maturity', $maturity),
            )
            ->orderByDesc('lexical_score')
            ->limit($limit)
            ->get();

        return $memories->map(fn (Memory $memory) => [
            'memory' => $memory,
            'lexical_score' => (float) ($memory->lexical_score ?? 0.0),
        ]);
    }
}
