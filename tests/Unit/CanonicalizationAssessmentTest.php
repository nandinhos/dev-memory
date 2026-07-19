<?php

namespace Tests\Unit;

use App\Services\Curation\CanonicalizationAssessment;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CanonicalizationAssessmentTest extends TestCase
{
    public function test_aceita_veredito_keep_sem_sugestao(): void
    {
        $a = CanonicalizationAssessment::fromArray([
            'assessment' => 'false_negative',
            'reasoning' => 'Context7 resolveu a biblioteca errada.',
            'recommendation' => 'keep',
            'confidence' => 0.9,
        ]);

        $this->assertSame('false_negative', $a->assessment);
        $this->assertSame('keep', $a->recommendation);
        $this->assertNull($a->suggestedTitle);
    }

    public function test_correct_exige_titulo_e_descricao(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CanonicalizationAssessment::fromArray([
            'assessment' => 'real_contradiction',
            'reasoning' => 'A doc contradiz.',
            'recommendation' => 'correct',
            'confidence' => 0.8,
            // faltam suggested_title / suggested_description
        ]);
    }

    public function test_correct_valido_preserva_sugestao(): void
    {
        $a = CanonicalizationAssessment::fromArray([
            'assessment' => 'real_contradiction',
            'reasoning' => 'A doc contradiz o uso descrito.',
            'recommendation' => 'correct',
            'suggested_title' => 'Título corrigido',
            'suggested_description' => 'Descrição corrigida',
            'confidence' => 0.85,
        ]);

        $this->assertSame('Título corrigido', $a->suggestedTitle);
        $this->assertSame('Descrição corrigida', $a->suggestedDescription);
    }

    public function test_rejeita_assessment_invalido(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CanonicalizationAssessment::fromArray([
            'assessment' => 'inventado',
            'reasoning' => 'x',
            'recommendation' => 'keep',
            'confidence' => 0.5,
        ]);
    }

    public function test_keep_descarta_sugestao_enviada(): void
    {
        // Viés de segurança: mesmo se o modelo mandar sugestão com "keep",
        // ela é descartada — nunca reescreve uma memória que deve ser mantida.
        $a = CanonicalizationAssessment::fromArray([
            'assessment' => 'not_library_documentable',
            'reasoning' => 'TDD é metodologia, não tem doc de biblioteca.',
            'recommendation' => 'keep',
            'suggested_title' => 'não deveria aparecer',
            'suggested_description' => 'idem',
            'confidence' => 0.95,
        ]);

        $this->assertNull($a->suggestedTitle);
        $this->assertNull($a->suggestedDescription);
    }
}
