<?php

namespace App\Contracts;

use App\Models\Memory;
use Illuminate\Support\Collection;

interface MemoryLexicalSearch
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array{memory: Memory, lexical_score: float}>
     */
    public function search(string $query, int $limit = 10, array $filters = []): Collection;
}
