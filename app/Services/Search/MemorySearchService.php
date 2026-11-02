<?php

namespace App\Services\Search;

use App\Contracts\MemorySimilaritySearch;
use App\Models\Memory;
use App\Services\Embeddings\EmbeddingGeneratorService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MemorySearchService
{
    private const RRF_K = 60;

    public function __construct(
        private EmbeddingGeneratorService $embeddingGenerator,
        private FeatureReranker $reranker,
    ) {}

    public function search(string $query, array $filters = [], int $limit = 10, bool $rerank = true): array
    {
        $query = trim($query);

        if ($query === '') {
            $memories = Memory::query()
                ->filter($filters);

            if (! empty($filters['maturity'])) {
                $memories->where('maturity', $filters['maturity']);
            }

            return [
                'results' => $memories->limit($limit)->get(),
                'search_mode' => 'filter',
                'reranked' => false,
            ];
        }

        $candidateLimit = min(max($limit * 3, 20), 100);
        $driver = DB::connection()->getDriverName();

        $lexicalDriver = $driver === 'pgsql'
            ? new PgsqlMemoryLexicalSearch
            : new SqliteMemoryLexicalSearch;

        $lexicalResults = $lexicalDriver->search($query, $candidateLimit, $filters);

        $embeddingData = $this->embeddingGenerator->generate($query);
        $semanticResults = collect();

        if ($embeddingData !== null) {
            /** @var MemorySimilaritySearch $searchDriver */
            $searchDriver = $driver === 'pgsql'
                ? new PgvectorMemorySimilaritySearch
                : new SqliteMemorySimilaritySearch;

            $semanticResults = $searchDriver->search(
                $embeddingData['embedding'],
                $embeddingData['model'],
                $candidateLimit,
                $filters,
            );
        }

        $pool = $this->fuse($semanticResults, $lexicalResults);

        $shouldRerank = $rerank && (bool) config('search.rerank.enabled', true);

        if ($shouldRerank) {
            $rankedPool = $this->reranker->rerank($pool, [
                'query' => $query,
                'filters' => $filters,
            ]);
        } else {
            $rankedPool = $pool->map(function (array $item) {
                $item['rerank_score'] = $item['rank_score'];

                return $item;
            });
        }

        return [
            'results' => $rankedPool->take($limit)->values(),
            'search_mode' => match (true) {
                $semanticResults->isNotEmpty() && $lexicalResults->isNotEmpty() => 'hybrid',
                $semanticResults->isNotEmpty() => 'semantic',
                default => 'lexical',
            },
            'reranked' => $shouldRerank,
        ];
    }

    /**
     * @param  Collection<int, array{memory: Memory, similarity: float}>  $semantic
     * @param  Collection<int, array{memory: Memory, lexical_score: float}>  $lexical
     * @return Collection<int, array{memory: Memory, similarity: float, lexical_score: float, rank_score: float}>
     */
    private function fuse(Collection $semantic, Collection $lexical): Collection
    {
        $k = (int) config('search.rrf_k', self::RRF_K);
        $ranked = [];

        foreach ($semantic->values() as $index => $item) {
            $id = $item['memory']->id;
            $ranked[$id] = [
                'memory' => $item['memory'],
                'similarity' => (float) $item['similarity'],
                'lexical_score' => 0.0,
                'rank_score' => 1 / ($k + $index + 1),
            ];
        }

        foreach ($lexical->values() as $index => $item) {
            $id = $item['memory']->id;
            $ranked[$id] ??= [
                'memory' => $item['memory'],
                'similarity' => 0.0,
                'lexical_score' => 0.0,
                'rank_score' => 0.0,
            ];
            $ranked[$id]['lexical_score'] = (float) $item['lexical_score'];
            $ranked[$id]['rank_score'] += 1 / ($k + $index + 1);
        }

        return collect($ranked)
            ->sortByDesc(fn (array $item) => [
                $item['rank_score'],
                $item['memory']->recurrence_count,
            ])
            ->values();
    }
}
