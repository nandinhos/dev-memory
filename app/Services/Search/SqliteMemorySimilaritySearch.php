<?php

namespace App\Services\Search;

use App\Contracts\MemorySimilaritySearch;
use App\Models\Memory;
use Illuminate\Support\Collection;

class SqliteMemorySimilaritySearch implements MemorySimilaritySearch
{
    public function search(array $embedding, int $limit = 10, array $filters = []): Collection
    {
        $query = Memory::query()->withEmbedding();
        $query->filter($filters);

        if (! empty($filters['maturity'])) {
            $query->where('maturity', $filters['maturity']);
        }

        $memories = $query->get();

        $scored = $memories->map(function (Memory $memory) use ($embedding) {
            $vector = $memory->embedding;
            $similarity = is_array($vector) ? $this->cosineSimilarity($embedding, $vector) : 0.0;

            return [
                'memory' => $memory,
                'similarity' => $similarity,
            ];
        });

        return $scored
            ->filter(fn ($item) => $item['similarity'] > 0.0)
            ->sortByDesc('similarity')
            ->take($limit)
            ->values();
    }

    private function cosineSimilarity(array $vecA, array $vecB): float
    {
        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        $count = min(count($vecA), count($vecB));
        if ($count === 0) {
            return 0.0;
        }

        for ($i = 0; $i < $count; $i++) {
            $valA = (float) $vecA[$i];
            $valB = (float) $vecB[$i];

            $dotProduct += $valA * $valB;
            $normA += $valA * $valA;
            $normB += $valB * $valB;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}
