<?php

namespace App\Services\Curation;

use App\Enums\SkillGroupStatus;
use App\Models\Memory;
use App\Models\SkillGroup;
use Illuminate\Support\Collection;

/**
 * Groups skill candidates that make up the SAME reusable operational
 * skill. The engine proposes; groups land as "proposed" and only a
 * human approval moves them toward compilation.
 */
class SkillGroupProposer
{
    public function __construct(
        private AnthropicCurationEngine $engine,
    ) {}

    /**
     * @param  Collection<int, Memory>  $candidates
     */
    public function propose(Collection $candidates): SkillGroupProposal
    {
        $validIds = $candidates->pluck('id')->all();

        return $this->engine->completeJson(
            $this->systemPrompt(),
            $this->userPrompt($candidates),
            fn (array $json) => SkillGroupProposal::fromArray($json, $validIds),
        );
    }

    /**
     * Persist proposed groups. Existing "proposed" groups are replaced;
     * approved/rejected/compiled groups are never touched.
     *
     * @return Collection<int, SkillGroup>
     */
    public function store(SkillGroupProposal $proposal): Collection
    {
        SkillGroup::where('status', SkillGroupStatus::PROPOSED)->get()
            ->each(fn (SkillGroup $group) => $group->delete());

        return collect($proposal->groups)->map(function (array $group) {
            $skillGroup = SkillGroup::create([
                'name' => $group['name'],
                'slug' => $group['slug'],
                'purpose' => $group['purpose'],
                'rationale' => $group['rationale'],
                'cohesion' => $group['cohesion'],
                'status' => SkillGroupStatus::PROPOSED,
            ]);

            $skillGroup->memories()->attach($group['memory_ids']);

            return $skillGroup;
        });
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
Você é um arquiteto de Skills — competências operacionais reutilizáveis por agentes de IA.

Receberá uma lista de memórias técnicas candidatas. Sua tarefa: identificar quais memórias compõem a MESMA habilidade operacional e agrupá-las.

CRITÉRIO DE COESÃO (o que faz memórias pertencerem à mesma skill):
- Mesmo propósito operacional: são consultadas juntas no mesmo momento de trabalho (ex.: "integrar Alpine com Livewire", "configurar ambiente Docker de desenvolvimento Laravel").
- Compartilhar stack NÃO basta — duas memórias de Laravel com propósitos distintos NÃO formam grupo.
- Um bom grupo vira uma skill com workflow coeso; um grupo ruim é só uma pilha de fatos soltos.

REGRAS:
1. Responda APENAS com o objeto JSON, sem markdown nem texto extra.
2. Todo grupo precisa de 2 ou mais memórias. Memória forte que sustenta uma skill sozinha vai em "standalone". Memória que não sustenta skill nenhuma vai em "excluded".
3. Cada memory_id aparece NO MÁXIMO uma vez em toda a resposta (grupos, standalone ou excluded).
4. Use exatamente os memory_ids fornecidos.
5. "cohesion": força do grupo entre 0 e 1 (1 = memórias inseparáveis operacionalmente).
6. "slug" em kebab-case descritivo.

CONTRATO:
{
  "groups": [
    {
      "name": string,
      "slug": string kebab-case,
      "purpose": string (o que a skill resultante permitirá fazer),
      "rationale": string (por que estas memórias formam uma única habilidade),
      "cohesion": número 0..1,
      "memory_ids": [string, ...]
    }
  ],
  "standalone": [{"memory_id": string, "reason": string}],
  "excluded": [{"memory_id": string, "reason": string}]
}
PROMPT;
    }

    private function userPrompt(Collection $candidates): string
    {
        $listing = $candidates->map(function (Memory $memory) {
            $summary = mb_substr(trim(preg_replace('/\s+/', ' ', $memory->description)), 0, 160);

            return "- memory_id: {$memory->id}\n"
                ."  título: {$memory->title}\n"
                ."  tipo: {$memory->type->value} | stack: ".($memory->stack ?? '—')." | recorrência: {$memory->recurrence_count}\n"
                ."  resumo: {$summary}";
        })->implode("\n\n");

        return "CANDIDATAS A SKILL ({$candidates->count()} memórias):\n\n{$listing}\n\n"
            .'TAREFA: agrupe as memórias que compõem a mesma habilidade operacional, conforme o contrato.';
    }
}
