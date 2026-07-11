<?php

namespace App\Services\Curation;

use InvalidArgumentException;

class LessonDraft
{
    public const CATEGORIES = ['error', 'lesson', 'best_practice'];

    public function __construct(
        public string $title,
        public string $summary,
        public string $problem,
        public ?string $rootCause,
        public string $solution,
        public string $category,
        public array $technologies,
        public array $evidence,
        public array $applicability,
        public array $risks,
        public float $confidence,
    ) {}

    /**
     * Build from decoded JSON, validating the full contract.
     * Collects every violation so the repair prompt can list them all.
     */
    public static function fromArray(array $data): self
    {
        $errors = [];

        foreach (['title', 'summary', 'problem', 'solution', 'category'] as $field) {
            if (! isset($data[$field]) || ! is_string($data[$field]) || trim($data[$field]) === '') {
                $errors[] = "campo '{$field}' ausente ou não é string não-vazia";
            }
        }

        if (isset($data['title']) && is_string($data['title'])) {
            $length = mb_strlen($data['title']);
            if ($length < 10 || $length > 160) {
                $errors[] = "campo 'title' deve ter entre 10 e 160 caracteres (tem {$length})";
            }
        }

        if (isset($data['category']) && ! in_array($data['category'], self::CATEGORIES, true)) {
            $errors[] = "campo 'category' deve ser um de: ".implode('|', self::CATEGORIES);
        }

        if (array_key_exists('root_cause', $data) && $data['root_cause'] !== null && ! is_string($data['root_cause'])) {
            $errors[] = "campo 'root_cause' deve ser string ou null";
        }

        if (! isset($data['technologies']) || ! is_array($data['technologies'])) {
            $errors[] = "campo 'technologies' ausente ou não é array";
        } else {
            foreach ($data['technologies'] as $index => $tech) {
                if (! is_array($tech) || ! isset($tech['name']) || ! is_string($tech['name'])) {
                    $errors[] = "technologies[{$index}] deve ser objeto com 'name' (string) e 'version' (string ou null)";
                }
            }
        }

        foreach (['evidence', 'applicability', 'risks'] as $field) {
            if (! isset($data[$field]) || ! is_array($data[$field])) {
                $errors[] = "campo '{$field}' ausente ou não é array de strings";
            }
        }

        if (! isset($data['confidence']) || ! is_numeric($data['confidence'])) {
            $errors[] = "campo 'confidence' ausente ou não é numérico";
        } elseif ($data['confidence'] < 0 || $data['confidence'] > 1) {
            $errors[] = "campo 'confidence' deve estar entre 0 e 1";
        }

        if ($errors !== []) {
            throw new InvalidArgumentException(implode('; ', $errors));
        }

        return new self(
            title: $data['title'],
            summary: $data['summary'],
            problem: $data['problem'],
            rootCause: $data['root_cause'] ?? null,
            solution: $data['solution'],
            category: $data['category'],
            technologies: array_values($data['technologies']),
            evidence: array_values($data['evidence']),
            applicability: array_values($data['applicability']),
            risks: array_values($data['risks']),
            confidence: (float) $data['confidence'],
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'summary' => $this->summary,
            'problem' => $this->problem,
            'root_cause' => $this->rootCause,
            'solution' => $this->solution,
            'category' => $this->category,
            'technologies' => $this->technologies,
            'evidence' => $this->evidence,
            'applicability' => $this->applicability,
            'risks' => $this->risks,
            'confidence' => $this->confidence,
        ];
    }
}
