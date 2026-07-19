<?php

namespace App\Services\Curation;

use InvalidArgumentException;

/**
 * Veredito estruturado da análise de uma memória marcada CONTRADITA pela checagem
 * documental. O foco é distinguir contradição REAL de falso-negativo (biblioteca
 * errada) ou erro de categoria (assunto não documentável por biblioteca) — só
 * propõe correção quando a contradição é real e fundamentada.
 */
final class CanonicalizationAssessment
{
    public function __construct(
        public string $assessment,
        public string $reasoning,
        public string $recommendation,
        public ?string $suggestedTitle,
        public ?string $suggestedDescription,
        public float $confidence,
    ) {}

    /** @return list<string> */
    public static function assessments(): array
    {
        return ['false_negative', 'not_library_documentable', 'real_contradiction', 'outdated'];
    }

    /** @return list<string> */
    public static function recommendations(): array
    {
        return ['keep', 'correct', 'reject'];
    }

    public static function fromArray(array $data): self
    {
        $errors = [];

        if (! isset($data['assessment']) || ! in_array($data['assessment'], self::assessments(), true)) {
            $errors[] = "campo 'assessment' deve ser um de: ".implode('|', self::assessments());
        }

        if (! isset($data['reasoning']) || ! is_string($data['reasoning']) || trim($data['reasoning']) === '') {
            $errors[] = "campo 'reasoning' ausente ou vazio";
        }

        if (! isset($data['recommendation']) || ! in_array($data['recommendation'], self::recommendations(), true)) {
            $errors[] = "campo 'recommendation' deve ser um de: ".implode('|', self::recommendations());
        }

        if (! isset($data['confidence']) || ! is_numeric($data['confidence'])) {
            $errors[] = "campo 'confidence' ausente ou não numérico";
        }

        // Correção só é aceita com o par título/descrição — nunca reescrever no vazio.
        if (($data['recommendation'] ?? null) === 'correct') {
            foreach (['suggested_title', 'suggested_description'] as $field) {
                if (empty($data[$field]) || ! is_string($data[$field])) {
                    $errors[] = "recommendation 'correct' exige '{$field}' não-vazio";
                }
            }
        }

        if ($errors !== []) {
            throw new InvalidArgumentException(implode('; ', $errors));
        }

        return new self(
            assessment: $data['assessment'],
            reasoning: $data['reasoning'],
            recommendation: $data['recommendation'],
            suggestedTitle: ($data['recommendation'] === 'correct') ? $data['suggested_title'] : null,
            suggestedDescription: ($data['recommendation'] === 'correct') ? $data['suggested_description'] : null,
            confidence: (float) $data['confidence'],
        );
    }

    public function toArray(): array
    {
        return [
            'assessment' => $this->assessment,
            'reasoning' => $this->reasoning,
            'recommendation' => $this->recommendation,
            'suggested_title' => $this->suggestedTitle,
            'suggested_description' => $this->suggestedDescription,
            'confidence' => $this->confidence,
        ];
    }
}
