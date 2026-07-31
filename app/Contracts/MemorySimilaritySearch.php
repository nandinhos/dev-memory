<?php

namespace App\Contracts;

use App\Models\Memory;
use Illuminate\Support\Collection;

interface MemorySimilaritySearch
{
    /**
     * Busca memórias por similaridade vetorial.
     *
     * @param  array<float>  $embedding
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array{memory: Memory, similarity: float}>
     */
    public function search(array $embedding, int $limit = 10, array $filters = []): Collection;
}
