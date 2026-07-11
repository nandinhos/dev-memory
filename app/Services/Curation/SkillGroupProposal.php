<?php

namespace App\Services\Curation;

use InvalidArgumentException;

class SkillGroupProposal
{
    public function __construct(
        public array $groups,
        public array $standalone,
        public array $excluded,
    ) {}

    /**
     * Validate against the actual candidate set: every referenced id must
     * exist in $validIds, no id may appear twice, and a group needs at
     * least two members (singletons belong in standalone).
     */
    public static function fromArray(array $data, array $validIds): self
    {
        $errors = [];
        $seen = [];

        $claim = function (string $memoryId, string $context) use (&$errors, &$seen, $validIds): void {
            if (! in_array($memoryId, $validIds, true)) {
                $errors[] = "{$context}: memory_id '{$memoryId}' não pertence ao conjunto de candidatas";
            } elseif (isset($seen[$memoryId])) {
                $errors[] = "{$context}: memory_id '{$memoryId}' aparece em mais de um lugar (já em {$seen[$memoryId]})";
            } else {
                $seen[$memoryId] = $context;
            }
        };

        if (! isset($data['groups']) || ! is_array($data['groups'])) {
            $errors[] = "campo 'groups' ausente ou não é array";
            $data['groups'] = [];
        }

        foreach ($data['groups'] as $index => $group) {
            $context = "groups[{$index}]";

            foreach (['name', 'slug', 'purpose', 'rationale'] as $field) {
                if (! isset($group[$field]) || ! is_string($group[$field]) || trim($group[$field]) === '') {
                    $errors[] = "{$context}: campo '{$field}' ausente ou vazio";
                }
            }

            if (isset($group['slug']) && ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $group['slug'])) {
                $errors[] = "{$context}: slug deve ser kebab-case";
            }

            if (! isset($group['cohesion']) || ! is_numeric($group['cohesion'])
                || $group['cohesion'] < 0 || $group['cohesion'] > 1) {
                $errors[] = "{$context}: 'cohesion' deve ser número entre 0 e 1";
            }

            $memberIds = $group['memory_ids'] ?? null;

            if (! is_array($memberIds) || count($memberIds) < 2) {
                $errors[] = "{$context}: 'memory_ids' precisa de ao menos 2 membros (singleton vai em standalone)";
            } else {
                foreach ($memberIds as $memoryId) {
                    $claim((string) $memoryId, $context);
                }
            }
        }

        foreach (['standalone', 'excluded'] as $bucket) {
            if (! isset($data[$bucket]) || ! is_array($data[$bucket])) {
                $errors[] = "campo '{$bucket}' ausente ou não é array";
                $data[$bucket] = [];

                continue;
            }

            foreach ($data[$bucket] as $index => $entry) {
                $context = "{$bucket}[{$index}]";

                if (! is_array($entry) || ! isset($entry['memory_id'], $entry['reason'])) {
                    $errors[] = "{$context}: precisa de 'memory_id' e 'reason'";

                    continue;
                }

                $claim((string) $entry['memory_id'], $context);
            }
        }

        if ($errors !== []) {
            throw new InvalidArgumentException(implode('; ', $errors));
        }

        return new self(
            groups: array_values($data['groups']),
            standalone: array_values($data['standalone']),
            excluded: array_values($data['excluded']),
        );
    }

    public function toArray(): array
    {
        return [
            'groups' => $this->groups,
            'standalone' => $this->standalone,
            'excluded' => $this->excluded,
        ];
    }
}
