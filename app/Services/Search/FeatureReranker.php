<?php

namespace App\Services\Search;

use App\Models\Memory;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class FeatureReranker
{
    /**
     * @param  Collection<int, array{memory: Memory, similarity: float, lexical_score: float, rank_score: float}>  $pool
     * @param  array<string, mixed>  $context
     * @return Collection<int, array{memory: Memory, similarity: float, lexical_score: float, rank_score: float, rerank_score: float}>
     */
    public function rerank(Collection $pool, array $context = []): Collection
    {
        return $pool->map(function (array $item) use ($context) {
            /** @var Memory $memory */
            $memory = $item['memory'];
            $baseRrf = (float) ($item['rank_score'] ?? 0.0);
            $item['rerank_score'] = $this->score($memory, $baseRrf, $context);

            return $item;
        })->sortByDesc(fn (array $item) => [
            $item['rerank_score'],
            $item['rank_score'],
            $item['memory']->recurrence_count,
        ])->values();
    }

    /**
     * Calcula o score reponderado com base nas características editoriais da memória.
     *
     * @param  array<string, mixed>  $context
     */
    public function score(Memory $memory, float $baseRrfScore, array $context = []): float
    {
        $validationWeights = config('search.rerank.weights.validation_status', []);
        $statusKey = $memory->validation_status?->value ?? 'pending';
        $wValidation = (float) ($validationWeights[$statusKey] ?? 1.0);

        $recurrenceFactor = (float) config('search.rerank.weights.recurrence_factor', 0.15);
        $recurrenceCount = max(0, (int) $memory->recurrence_count);
        $wRecurrence = 1.0 + (log($recurrenceCount + 1, 2) * $recurrenceFactor);

        $docWeights = config('search.rerank.weights.doc_validation', []);
        $docKey = $memory->doc_validation_status?->value ?? 'unverified';
        $wDoc = (float) ($docWeights[$docKey] ?? 1.0);

        $stackBoost = 0.0;
        if (! empty($memory->stack)) {
            $memoryStack = mb_strtolower(trim((string) $memory->stack));
            $targetStack = mb_strtolower(trim((string) ($context['filters']['stack'] ?? '')));
            $query = mb_strtolower(trim((string) ($context['query'] ?? '')));

            if ($targetStack !== '' && $targetStack === $memoryStack) {
                $stackBoost = (float) config('search.rerank.weights.stack_boost', 0.20);
            } elseif ($query !== '' && (str_contains($query, $memoryStack) || str_contains($memoryStack, $query))) {
                $stackBoost = (float) config('search.rerank.weights.stack_boost', 0.20);
            }
        }

        $wRecency = 1.0;
        $date = $memory->updated_at ?? $memory->created_at;
        if ($date instanceof CarbonInterface) {
            $days = $date->diffInDays(now());
            $freshDays = (int) config('search.rerank.weights.recency.fresh_days', 60);
            $staleDays = (int) config('search.rerank.weights.recency.stale_days', 180);

            if ($days <= $freshDays) {
                $wRecency = (float) config('search.rerank.weights.recency.fresh_boost', 1.05);
            } elseif ($days > $staleDays && $recurrenceCount <= 1) {
                $wRecency = (float) config('search.rerank.weights.recency.stale_penalty', 0.95);
            }
        }

        $maturityWeights = config('search.rerank.weights.maturity', []);
        $maturityKey = $memory->maturity?->value ?? 'provisional';
        $wMaturity = (float) ($maturityWeights[$maturityKey] ?? 1.0);

        return $baseRrfScore
            * $wValidation
            * $wRecurrence
            * $wDoc
            * (1.0 + $stackBoost)
            * $wRecency
            * $wMaturity;
    }
}
