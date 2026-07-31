<?php

namespace App\Services\Search;

use App\Contracts\MemorySimilaritySearch;
use App\Models\Memory;
use App\Services\Embeddings\EmbeddingGeneratorService;
use Illuminate\Support\Facades\DB;

class MemorySearchService
{
    public function __construct(
        private EmbeddingGeneratorService $embeddingGenerator,
    ) {}

    public function search(string $query, array $filters = [], int $limit = 10): array
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
            ];
        }

        // Tentar gerar embedding para busca semântica
        $embeddingData = $this->embeddingGenerator->generate($query);

        if ($embeddingData !== null) {
            $driver = DB::connection()->getDriverName();
            /** @var MemorySimilaritySearch $searchDriver */
            $searchDriver = $driver === 'pgsql'
                ? new PgvectorMemorySimilaritySearch
                : new SqliteMemorySimilaritySearch;

            $semanticResults = $searchDriver->search($embeddingData['embedding'], $limit, $filters);

            if ($semanticResults->isNotEmpty()) {
                return [
                    'results' => $semanticResults,
                    'search_mode' => 'semantic',
                ];
            }
        }

        // Fallback lexical LIKE
        $lexicalResults = Memory::query()
            ->filter(array_merge($filters, ['search' => $query]));

        if (! empty($filters['maturity'])) {
            $lexicalResults->where('maturity', $filters['maturity']);
        }

        return [
            'results' => $lexicalResults->limit($limit)->get()->map(fn (Memory $memory) => [
                'memory' => $memory,
                'similarity' => 0.0,
            ]),
            'search_mode' => 'lexical_fallback',
        ];
    }
}
