<?php

namespace App\Services\Curation;

class SkillMarkdownRenderer
{
    public function render(array $manifest): string
    {
        $schemaVersion = $manifest['schema_version'] ?? '1.0';

        $lines = [
            "# {$manifest['name']}",
            '',
            "**Slug:** `{$manifest['slug']}` · **Schema:** {$schemaVersion}",
            '',
            "## Propósito\n{$manifest['purpose']}",
            '',
            '## Ativação',
            '- **Tecnologias:** '.implode(', ', $manifest['activation']['technologies']),
            '- **Gatilhos:** '.implode(' · ', $manifest['activation']['triggers']),
            '',
        ];

        if (($manifest['preconditions'] ?? []) !== []) {
            $lines[] = "## Pré-condições\n- ".implode("\n- ", $manifest['preconditions'])."\n";
        }

        $lines[] = '## Workflow';

        foreach ($manifest['workflow'] as $step) {
            $lines[] = "{$step['order']}. {$step['action']}";

            if (! empty($step['validation'])) {
                $lines[] = "   - *Validação:* {$step['validation']}";
            }
        }

        $lines[] = '';

        if (($manifest['guardrails'] ?? []) !== []) {
            $lines[] = "## Guardrails\n- ".implode("\n- ", $manifest['guardrails'])."\n";
        }

        if (($manifest['anti_patterns'] ?? []) !== []) {
            $lines[] = "## Antipadrões\n- ".implode("\n- ", $manifest['anti_patterns'])."\n";
        }

        if (($manifest['test_cases'] ?? []) !== []) {
            $lines[] = '## Casos de teste';

            foreach ($manifest['test_cases'] as $case) {
                $lines[] = "- **{$case['name']}** → {$case['expected']}";
            }

            $lines[] = '';
        }

        $lines[] = '## Evidências';
        $lines[] = '- **Memórias de origem:** '.implode(', ', $manifest['evidence']['lesson_ids']);

        if (($manifest['evidence']['official_sources'] ?? []) !== []) {
            $lines[] = "- **Fontes oficiais:**\n  - ".implode("\n  - ", $manifest['evidence']['official_sources']);
        }

        return implode("\n", $lines)."\n";
    }
}
