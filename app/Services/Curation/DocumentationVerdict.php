<?php

namespace App\Services\Curation;

use App\Enums\DocumentationValidationStatus;
use InvalidArgumentException;

class DocumentationVerdict
{
    public const CLAIM_VERDICTS = ['supported', 'unsupported', 'contradicted'];

    public function __construct(
        public DocumentationValidationStatus $status,
        public array $claims,
        public array $versionConstraints,
        public float $confidence,
    ) {}

    public static function fromArray(array $data): self
    {
        $errors = [];

        $allowedStatuses = [
            DocumentationValidationStatus::CONFIRMED->value,
            DocumentationValidationStatus::PARTIALLY_CONFIRMED->value,
            DocumentationValidationStatus::CONTRADICTED->value,
            DocumentationValidationStatus::INCONCLUSIVE->value,
        ];

        if (! isset($data['status']) || ! in_array($data['status'], $allowedStatuses, true)) {
            $errors[] = "campo 'status' deve ser um de: ".implode('|', $allowedStatuses);
        }

        if (! isset($data['claims']) || ! is_array($data['claims'])) {
            $errors[] = "campo 'claims' ausente ou não é array";
        } else {
            foreach ($data['claims'] as $index => $claim) {
                if (
                    ! is_array($claim)
                    || ! isset($claim['claim']) || ! is_string($claim['claim'])
                    || ! isset($claim['verdict']) || ! in_array($claim['verdict'], self::CLAIM_VERDICTS, true)
                ) {
                    $errors[] = "claims[{$index}] deve ter 'claim' (string) e 'verdict' (".implode('|', self::CLAIM_VERDICTS).')';
                }
            }
        }

        if (! isset($data['version_constraints']) || ! is_array($data['version_constraints'])) {
            $errors[] = "campo 'version_constraints' ausente ou não é array";
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
            status: DocumentationValidationStatus::from($data['status']),
            claims: array_values($data['claims']),
            versionConstraints: array_values($data['version_constraints']),
            confidence: (float) $data['confidence'],
        );
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'claims' => $this->claims,
            'version_constraints' => $this->versionConstraints,
            'confidence' => $this->confidence,
        ];
    }
}
