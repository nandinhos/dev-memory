<?php

namespace Tests\Unit\Services;

use App\Enums\DocumentationValidationStatus;
use App\Enums\ValidationStatus;
use App\Models\Memory;
use App\Services\Search\FeatureReranker;
use Tests\TestCase;

class FeatureRerankerTest extends TestCase
{
    private FeatureReranker $reranker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reranker = new FeatureReranker;
    }

    public function test_validated_memory_receives_higher_score_than_pending(): void
    {
        $validated = new Memory([
            'validation_status' => ValidationStatus::VALIDATED,
            'recurrence_count' => 0,
        ]);

        $pending = new Memory([
            'validation_status' => ValidationStatus::PENDING,
            'recurrence_count' => 0,
        ]);

        $scoreValidated = $this->reranker->score($validated, 0.05);
        $scorePending = $this->reranker->score($pending, 0.05);

        $this->assertGreaterThan($scorePending, $scoreValidated);
        $this->assertEqualsWithDelta(0.05 * 1.30, $scoreValidated, 0.0001);
        $this->assertEqualsWithDelta(0.05 * 1.00, $scorePending, 0.0001);
    }

    public function test_contradicted_doc_validation_penalizes_score(): void
    {
        $confirmed = new Memory([
            'validation_status' => ValidationStatus::PENDING,
            'doc_validation_status' => DocumentationValidationStatus::CONFIRMED,
            'recurrence_count' => 0,
        ]);

        $contradicted = new Memory([
            'validation_status' => ValidationStatus::PENDING,
            'doc_validation_status' => DocumentationValidationStatus::CONTRADICTED,
            'recurrence_count' => 0,
        ]);

        $scoreConfirmed = $this->reranker->score($confirmed, 0.05);
        $scoreContradicted = $this->reranker->score($contradicted, 0.05);

        $this->assertGreaterThan($scoreContradicted, $scoreConfirmed);
        $this->assertEqualsWithDelta(0.05 * 1.25, $scoreConfirmed, 0.0001);
        $this->assertEqualsWithDelta(0.05 * 0.40, $scoreContradicted, 0.0001);
    }

    public function test_stack_match_boosts_score(): void
    {
        $memory = new Memory([
            'validation_status' => ValidationStatus::PENDING,
            'stack' => 'docker',
            'recurrence_count' => 0,
        ]);

        $scoreWithFilterMatch = $this->reranker->score($memory, 0.05, ['filters' => ['stack' => 'Docker']]);
        $scoreWithQueryMatch = $this->reranker->score($memory, 0.05, ['query' => 'ajustar docker container']);
        $scoreWithoutMatch = $this->reranker->score($memory, 0.05, ['query' => 'laravel routing']);

        $this->assertEqualsWithDelta(0.05 * 1.20, $scoreWithFilterMatch, 0.0001);
        $this->assertEqualsWithDelta(0.05 * 1.20, $scoreWithQueryMatch, 0.0001);
        $this->assertEqualsWithDelta(0.05 * 1.00, $scoreWithoutMatch, 0.0001);
    }

    public function test_recurrence_increases_score_smoothly(): void
    {
        $zeroRecurrence = new Memory([
            'validation_status' => ValidationStatus::PENDING,
            'recurrence_count' => 0,
        ]);

        $threeRecurrences = new Memory([
            'validation_status' => ValidationStatus::PENDING,
            'recurrence_count' => 3, // log2(4) = 2 -> 1 + 2*0.15 = 1.30
        ]);

        $scoreZero = $this->reranker->score($zeroRecurrence, 0.05);
        $scoreThree = $this->reranker->score($threeRecurrences, 0.05);

        $this->assertEqualsWithDelta(0.05 * 1.00, $scoreZero, 0.0001);
        $this->assertEqualsWithDelta(0.05 * 1.30, $scoreThree, 0.0001);
    }

    public function test_rerank_promotes_validated_and_recurrent_memory_over_higher_initial_rrf(): void
    {
        $memoryA = new Memory([
            'id' => '00000000-0000-0000-0000-000000000001',
            'title' => 'Memória A (Pending, sem recorrência)',
            'validation_status' => ValidationStatus::PENDING,
            'recurrence_count' => 0,
        ]);

        $memoryB = new Memory([
            'id' => '00000000-0000-0000-0000-000000000002',
            'title' => 'Memória B (Validada, 3 recorrências)',
            'validation_status' => ValidationStatus::VALIDATED,
            'doc_validation_status' => DocumentationValidationStatus::CONFIRMED,
            'recurrence_count' => 3,
        ]);

        // No RRF inicial: A está em 1º (rank_score = 0.01639), B está em 2º (rank_score = 0.01612)
        $pool = collect([
            [
                'memory' => $memoryA,
                'similarity' => 0.85,
                'lexical_score' => 0.80,
                'rank_score' => 0.01639,
            ],
            [
                'memory' => $memoryB,
                'similarity' => 0.84,
                'lexical_score' => 0.79,
                'rank_score' => 0.01612,
            ],
        ]);

        $reranked = $this->reranker->rerank($pool);

        // Após rerank: B deve ultrapassar A no topo devido à validação e recorrência
        $this->assertSame($memoryB->id, $reranked->first()['memory']->id);
        $this->assertGreaterThan($reranked->last()['rerank_score'], $reranked->first()['rerank_score']);
    }

    public function test_normalizes_rerank_score_to_zero_to_one_scale(): void
    {
        // Score muito alto não ultrapassa 1.0000
        $this->assertSame(1.0, $this->reranker->normalize(0.120));

        // Score zero permanece 0.0000
        $this->assertSame(0.0, $this->reranker->normalize(0.0));

        // Score intermediário é escalonado proporcionalmente ao teto padrão (0.060)
        // 0.030 / 0.060 = 0.5000
        $this->assertSame(0.5, $this->reranker->normalize(0.030));

        // Score típico de item validado (ex: 0.045) -> 0.045 / 0.060 = 0.7500
        $this->assertSame(0.75, $this->reranker->normalize(0.045));
    }
}
