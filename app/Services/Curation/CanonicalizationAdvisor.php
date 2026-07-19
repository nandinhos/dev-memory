<?php

namespace App\Services\Curation;

use App\Models\Memory;

/**
 * Análise assistida por IA de memórias marcadas CONTRADITAS pela checagem documental.
 * Reusa o relatório do Context7 já salvo (barato, sem re-consultar) e pede ao motor
 * um veredito crítico: a contradição é real ou espúria? Só sugere correção quando é
 * real e fundamentada — o viés é MANTER, para nunca reescrever uma memória boa
 * contra uma documentação errada.
 */
class CanonicalizationAdvisor
{
    public function __construct(private AnthropicCurationEngine $engine) {}

    public function assess(Memory $memory): CanonicalizationAssessment
    {
        return $this->engine->completeJson(
            $this->systemPrompt(),
            $this->userPrompt($memory),
            fn (array $json) => CanonicalizationAssessment::fromArray($json),
        );
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
Você é um curador sênior de conhecimento técnico. Uma memória foi marcada como CONTRADITA por uma checagem automática contra documentação oficial (via Context7). Sua tarefa é AVALIAR CRITICAMENTE se a contradição é REAL antes de qualquer correção.

CONTEXTO CRÍTICO — leia com atenção:
A checagem automática erra com frequência por RESOLVER A BIBLIOTECA ERRADA. Exemplo real: "TDD" (a metodologia) foi resolvido para "TDD Guard" (uma ferramenta CLI), então a memória foi comparada com documentação irrelevante e marcada como contradita à toa. Reescrever uma memória BOA para casar com a documentação ERRADA corrompe a base de conhecimento — é o pior resultado possível. Por isso, na dúvida, MANTENHA.

Classifique em "assessment":
- "false_negative": a checagem comparou a memória com a documentação de uma biblioteca/ferramenta DIFERENTE do assunto real. A contradição é espúria.
- "not_library_documentable": o assunto é uma metodologia, prática, convenção ou princípio (ex.: TDD, Conventional Commits, SOLID, Clean Architecture, DDD) que NÃO tem documentação oficial de biblioteca. Uma "contradição" do Context7 aqui é um erro de categoria.
- "real_contradiction": a documentação oficial CORRETA e RELEVANTE de fato contradiz a memória (ex.: uso incorreto de uma API, comportamento mal descrito).
- "outdated": a memória era correta em uma versão antiga, mas a documentação atual mudou.

Escolha "recommendation":
- "keep": manter a memória como está. USE para "false_negative" e "not_library_documentable".
- "correct": propor uma versão corrigida. USE apenas para "real_contradiction" ou "outdated", e SOMENTE se você conseguir fundamentar a correção nas notas fornecidas.
- "reject": a memória é factualmente errada e não vale a pena corrigir.

REGRAS INEGOCIÁVEIS:
1. Só use "correct" quando a contradição for REAL e fundamentável nas notas da checagem. NUNCA invente suporte nem reescreva por padrão.
2. Para "correct", forneça "suggested_title" e "suggested_description" — a versão canônica corrigida, preservando o valor da lição e ajustando só o que a documentação exige.
3. Para "keep" e "reject", "suggested_title" e "suggested_description" DEVEM ser null.
4. Responda APENAS com o objeto JSON. Sem markdown, sem texto antes ou depois.

CONTRATO (todos os campos obrigatórios):
{
  "assessment": "false_negative" | "not_library_documentable" | "real_contradiction" | "outdated",
  "reasoning": string (por que classificou assim, citando as notas da checagem),
  "recommendation": "keep" | "correct" | "reject",
  "suggested_title": string ou null,
  "suggested_description": string ou null,
  "confidence": número entre 0 e 1
}
PROMPT;
    }

    private function userPrompt(Memory $memory): string
    {
        $report = $memory->doc_validation_report ?? [];
        $verdict = $report['verdict'] ?? [];
        $library = $report['library'] ?? '(não informada)';

        $claims = '';
        foreach ($verdict['claims'] ?? [] as $claim) {
            $c = $claim['claim'] ?? '';
            $v = $claim['verdict'] ?? '';
            $n = $claim['notes'] ?? '';
            $claims .= "- \"{$c}\" → {$v}".($n !== '' ? ": {$n}" : '')."\n";
        }

        if ($claims === '') {
            $claims = "(sem afirmações detalhadas no relatório)\n";
        }

        $sources = implode(', ', $report['sources'] ?? []) ?: '(nenhuma)';

        return "MEMÓRIA AVALIADA:\n"
            ."Título: {$memory->title}\n"
            ."Stack declarada: {$memory->stack}\n"
            ."Conteúdo:\n{$memory->description}\n\n"
            ."RESULTADO DA CHECAGEM AUTOMÁTICA (Context7):\n"
            ."Biblioteca consultada pelo Context7: {$library}\n"
            ."Afirmações e vereditos por afirmação:\n{$claims}\n"
            ."Fontes recuperadas: {$sources}\n\n"
            .'TAREFA: A biblioteca consultada corresponde ao assunto real da memória? A contradição é real, espúria (biblioteca errada) ou um erro de categoria (assunto não documentável por biblioteca)? Produza o JSON do veredito.';
    }
}
