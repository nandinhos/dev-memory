<?php

namespace App\Services\Curation;

use App\Enums\SkillGroupStatus;
use App\Enums\SkillStatus;
use App\Models\Memory;
use App\Models\Skill;
use App\Models\SkillGroup;
use Illuminate\Support\Facades\Storage;

/**
 * Compiles an approved skill group into a SkillCandidate manifest.
 * The engine consolidates; the contract forbids invented references
 * (official_sources must come from the provided source list) and the
 * skill lands as draft — publication remains a human decision.
 */
class SkillCompiler
{
    public function __construct(
        private AnthropicCurationEngine $engine,
    ) {}

    public function compile(SkillGroup $group): Skill
    {
        $memories = $group->memories()->get();
        $allowedIds = $memories->pluck('id')->all();
        $allowedSources = $this->collectSources($memories);

        $candidate = $this->engine->completeJson(
            $this->systemPrompt(),
            $this->userPrompt($group, $memories, $allowedSources),
            fn (array $json) => SkillCandidate::fromArray($json, $allowedIds, $allowedSources),
        );

        $skill = Skill::updateOrCreate(
            ['slug' => $candidate->slug],
            [
                'skill_group_id' => $group->id,
                'name' => $candidate->name,
                'manifest' => $candidate->toArray()
                    + ['engine' => $this->engine->lastMeta(), 'compiled_at' => now()->toIso8601String()],
                'status' => SkillStatus::DRAFT,
            ],
        );

        $group->update(['status' => SkillGroupStatus::COMPILED]);

        Storage::disk('local')->put(
            "skills/{$candidate->slug}.md",
            $this->renderMarkdown($candidate),
        );

        return $skill;
    }

    /**
     * Every source offered to the engine is real and verifiable:
     * the memory's own official_reference plus the URLs extracted from
     * Context7 excerpts during documentation validation.
     */
    private function collectSources($memories): array
    {
        $sources = [];

        foreach ($memories as $memory) {
            $reference = trim((string) $memory->official_reference);

            if (str_starts_with($reference, 'http')) {
                $sources[] = $reference;
            }

            foreach ($memory->doc_validation_report['sources'] ?? [] as $url) {
                $sources[] = $url;
            }
        }

        return array_values(array_unique($sources));
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
Você é um compilador de Skills — competências operacionais reutilizáveis por agentes de IA.

Receberá um grupo aprovado de memórias técnicas validadas e uma lista de FONTES OFICIAIS DISPONÍVEIS. Consolide as memórias em UMA skill coesa.

REGRAS INEGOCIÁVEIS:
1. Responda APENAS com o objeto JSON, sem markdown nem texto extra.
2. Em "evidence.lesson_ids", use exatamente os memory_ids fornecidos (todos os que sustentam a skill).
3. Em "evidence.official_sources", use APENAS URLs da lista de FONTES OFICIAIS DISPONÍVEIS. NUNCA invente, adapte ou complete uma referência. Se nenhuma fonte se aplica, use array vazio.
4. "workflow" em passos ordenados e acionáveis; "guardrails" são proibições objetivas; "anti_patterns" são o que evitar e por quê.
5. "test_cases" descrevem verificações objetivas de que a skill foi aplicada corretamente.

CONTRATO:
{
  "schema_version": "1.0",
  "slug": string kebab-case,
  "name": string,
  "purpose": string,
  "activation": {"technologies": [string], "triggers": [string]},
  "preconditions": [string],
  "workflow": [{"order": int, "action": string, "validation": string ou null}],
  "guardrails": [string],
  "anti_patterns": [string],
  "evidence": {"lesson_ids": [string], "official_sources": [string]},
  "test_cases": [{"name": string, "expected": string}]
}
PROMPT;
    }

    private function userPrompt(SkillGroup $group, $memories, array $allowedSources): string
    {
        $listing = $memories->map(function (Memory $memory) {
            return "- memory_id: {$memory->id}\n"
                ."  título: {$memory->title}\n"
                ."  tipo: {$memory->type->value} | stack: ".($memory->stack ?? '—')."\n"
                ."  conteúdo:\n".trim($memory->description);
        })->implode("\n\n");

        $sources = $allowedSources === []
            ? '(nenhuma — use official_sources: [])'
            : '- '.implode("\n- ", $allowedSources);

        return "GRUPO APROVADO: {$group->name}\n"
            ."Propósito: {$group->purpose}\n"
            ."Justificativa do agrupamento: {$group->rationale}\n\n"
            ."MEMÓRIAS DO GRUPO ({$memories->count()}):\n\n{$listing}\n\n"
            ."FONTES OFICIAIS DISPONÍVEIS:\n{$sources}\n\n"
            .'TAREFA: compile a skill conforme o contrato.';
    }

    private function renderMarkdown(SkillCandidate $candidate): string
    {
        $lines = [
            "# {$candidate->name}",
            '',
            "**Slug:** `{$candidate->slug}` · **Schema:** {$candidate->schemaVersion}",
            '',
            "## Propósito\n{$candidate->purpose}",
            '',
            '## Ativação',
            '- **Tecnologias:** '.implode(', ', $candidate->activation['technologies']),
            '- **Gatilhos:** '.implode(' · ', $candidate->activation['triggers']),
            '',
        ];

        if ($candidate->preconditions !== []) {
            $lines[] = "## Pré-condições\n- ".implode("\n- ", $candidate->preconditions)."\n";
        }

        $lines[] = '## Workflow';

        foreach ($candidate->workflow as $step) {
            $lines[] = "{$step['order']}. {$step['action']}";

            if (! empty($step['validation'])) {
                $lines[] = "   - *Validação:* {$step['validation']}";
            }
        }

        $lines[] = '';

        if ($candidate->guardrails !== []) {
            $lines[] = "## Guardrails\n- ".implode("\n- ", $candidate->guardrails)."\n";
        }

        if ($candidate->antiPatterns !== []) {
            $lines[] = "## Antipadrões\n- ".implode("\n- ", $candidate->antiPatterns)."\n";
        }

        if ($candidate->testCases !== []) {
            $lines[] = '## Casos de teste';

            foreach ($candidate->testCases as $case) {
                $lines[] = "- **{$case['name']}** → {$case['expected']}";
            }

            $lines[] = '';
        }

        $lines[] = '## Evidências';
        $lines[] = '- **Memórias de origem:** '.implode(', ', $candidate->evidence['lesson_ids']);

        if ($candidate->evidence['official_sources'] !== []) {
            $lines[] = "- **Fontes oficiais:**\n  - ".implode("\n  - ", $candidate->evidence['official_sources']);
        }

        return implode("\n", $lines)."\n";
    }
}
