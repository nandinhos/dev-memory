<?php

namespace App\Services\Search;

use App\Contracts\MemorySimilaritySearch;
use App\Models\Memory;
use Illuminate\Support\Collection;

class PgvectorMemorySimilaritySearch implements MemorySimilaritySearch
{
    public function search(
        array $embedding,
        string $embeddingModel,
        int $limit = 10,
        array $filters = [],
    ): Collection {
        $vectorString = '['.implode(',', $embedding).']';

        $query = Memory::query()
            ->withEmbedding()
            ->where('embedding_model', $embeddingModel)
            ->select('*')
            ->selectRaw('(1 - (embedding <=> ?::vector)) as similarity', [$vectorString]);

        $query->filter($filters);

        if (! empty($filters['maturity'])) {
            $query->where('maturity', $filters['maturity']);
        }

        $results = $query
            ->whereRaw('(1 - (embedding <=> ?::vector)) > 0', [$vectorString])
            ->orderByRaw('(embedding <=> ?::vector) ASC', [$vectorString])
            ->limit($limit)
            ->get();

        return $results->map(fn (Memory $memory) => [
            'memory' => $memory,
            'similarity' => (float) ($memory->similarity ?? 0.0),
        ]);
    }
}
