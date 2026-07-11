<?php

namespace App\Services\Curation;

use InvalidArgumentException;

class SkillCandidate
{
    public function __construct(
        public string $schemaVersion,
        public string $slug,
        public string $name,
        public string $purpose,
        public array $activation,
        public array $preconditions,
        public array $workflow,
        public array $guardrails,
        public array $antiPatterns,
        public array $evidence,
        public array $testCases,
    ) {}

    /**
     * Validate the full contract. Source traceability is enforced here:
     * evidence.lesson_ids must belong to the group's memories and
     * evidence.official_sources must be a subset of the sources actually
     * provided to the engine — an invented reference fails the contract.
     */
    public static function fromArray(array $data, array $allowedMemoryIds, array $allowedSources): self
    {
        $errors = [];

        foreach (['slug', 'name', 'purpose'] as $field) {
            if (! isset($data[$field]) || ! is_string($data[$field]) || trim($data[$field]) === '') {
                $errors[] = "campo '{$field}' ausente ou vazio";
            }
        }

        if (isset($data['slug']) && ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $data['slug'])) {
            $errors[] = "campo 'slug' deve ser kebab-case";
        }

        $activation = $data['activation'] ?? null;

        if (
            ! is_array($activation)
            || ! isset($activation['technologies']) || ! is_array($activation['technologies'])
            || ! isset($activation['triggers']) || ! is_array($activation['triggers'])
            || $activation['triggers'] === []
        ) {
            $errors[] = "campo 'activation' precisa de 'technologies' (array) e 'triggers' (array não-vazio)";
        }

        foreach (['preconditions', 'guardrails', 'anti_patterns'] as $field) {
            if (! isset($data[$field]) || ! is_array($data[$field])) {
                $errors[] = "campo '{$field}' ausente ou não é array";
            }
        }

        $workflow = $data['workflow'] ?? null;

        if (! is_array($workflow) || $workflow === []) {
            $errors[] = "campo 'workflow' precisa de ao menos 1 passo";
        } else {
            foreach ($workflow as $index => $step) {
                if (
                    ! is_array($step)
                    || ! isset($step['order']) || ! is_int($step['order'])
                    || ! isset($step['action']) || ! is_string($step['action'])
                ) {
                    $errors[] = "workflow[{$index}] precisa de 'order' (int) e 'action' (string)";
                }
            }
        }

        $evidence = $data['evidence'] ?? null;

        if (
            ! is_array($evidence)
            || ! isset($evidence['lesson_ids']) || ! is_array($evidence['lesson_ids'])
            || $evidence['lesson_ids'] === []
            || ! isset($evidence['official_sources']) || ! is_array($evidence['official_sources'])
        ) {
            $errors[] = "campo 'evidence' precisa de 'lesson_ids' (array não-vazio) e 'official_sources' (array)";
        } else {
            foreach ($evidence['lesson_ids'] as $memoryId) {
                if (! in_array($memoryId, $allowedMemoryIds, true)) {
                    $errors[] = "evidence.lesson_ids: '{$memoryId}' não pertence às memórias do grupo";
                }
            }

            foreach ($evidence['official_sources'] as $source) {
                if (! in_array($source, $allowedSources, true)) {
                    $errors[] = "evidence.official_sources: '{$source}' não está entre as fontes fornecidas — referência inventada";
                }
            }
        }

        if (! isset($data['test_cases']) || ! is_array($data['test_cases'])) {
            $errors[] = "campo 'test_cases' ausente ou não é array";
        } else {
            foreach ($data['test_cases'] as $index => $case) {
                if (! is_array($case) || ! isset($case['name'], $case['expected'])) {
                    $errors[] = "test_cases[{$index}] precisa de 'name' e 'expected'";
                }
            }
        }

        if ($errors !== []) {
            throw new InvalidArgumentException(implode('; ', $errors));
        }

        return new self(
            schemaVersion: (string) ($data['schema_version'] ?? '1.0'),
            slug: $data['slug'],
            name: $data['name'],
            purpose: $data['purpose'],
            activation: $data['activation'],
            preconditions: array_values($data['preconditions']),
            workflow: array_values($data['workflow']),
            guardrails: array_values($data['guardrails']),
            antiPatterns: array_values($data['anti_patterns']),
            evidence: $data['evidence'],
            testCases: array_values($data['test_cases']),
        );
    }

    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'slug' => $this->slug,
            'name' => $this->name,
            'purpose' => $this->purpose,
            'activation' => $this->activation,
            'preconditions' => $this->preconditions,
            'workflow' => $this->workflow,
            'guardrails' => $this->guardrails,
            'anti_patterns' => $this->antiPatterns,
            'evidence' => $this->evidence,
            'test_cases' => $this->testCases,
        ];
    }
}
