<?php

namespace Tests\Unit;

use App\Services\Curation\DocumentationVerdict;
use App\Services\Curation\LessonDraft;
use App\Services\Curation\PromotionPolicy;
use PHPUnit\Framework\TestCase;

class PromotionPolicyTest extends TestCase
{
    private function draft(float $confidence): LessonDraft
    {
        return LessonDraft::fromArray([
            'title' => 'Título de teste com tamanho válido',
            'summary' => 'Resumo técnico com tamanho suficiente.',
            'problem' => 'problema',
            'root_cause' => null,
            'solution' => 'solução',
            'category' => 'lesson',
            'technologies' => [],
            'evidence' => [],
            'applicability' => [],
            'risks' => [],
            'confidence' => $confidence,
        ]);
    }

    public function test_persists_draft_at_or_above_floor(): void
    {
        $policy = new PromotionPolicy;

        $this->assertTrue($policy->evaluate($this->draft(0.5))->persist);
        $this->assertTrue($policy->evaluate($this->draft(0.95))->persist);
    }

    public function test_discards_draft_below_floor_with_reason(): void
    {
        $decision = (new PromotionPolicy)->evaluate($this->draft(0.25));

        $this->assertFalse($decision->persist);
        $this->assertNotEmpty($decision->reasons);
        $this->assertStringContainsString('confidence', $decision->reasons[0]);
    }

    public function test_auto_validates_only_confirmed_with_high_confidence(): void
    {
        $policy = new PromotionPolicy;

        $verdict = fn (string $status, float $confidence) => DocumentationVerdict::fromArray([
            'status' => $status,
            'claims' => [['claim' => 'afirmação', 'verdict' => 'supported']],
            'version_constraints' => [],
            'confidence' => $confidence,
        ]);

        $this->assertTrue($policy->shouldAutoValidate($verdict('confirmed', 0.8)));
        $this->assertFalse($policy->shouldAutoValidate($verdict('confirmed', 0.79)));
        $this->assertFalse($policy->shouldAutoValidate($verdict('partially_confirmed', 0.95)));
        $this->assertFalse($policy->shouldAutoValidate($verdict('contradicted', 0.95)));
    }
}
