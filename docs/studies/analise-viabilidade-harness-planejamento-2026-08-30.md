# Análise de Viabilidade — Harness de planejamento acoplado ao dev-memory

**Data:** 2026-08-30
**Objeto:** acoplar o fluxo de planejamento de um harness (`bc-harness` `/plan`) ao hub, para que a memória acumulada seja **fonte de evidência do planejamento** e o retrabalho sobre erros já cometidos deixe de acontecer
**Relação:** irmão de [`analise-viabilidade-rerank-2026-08-30.md`](analise-viabilidade-rerank-2026-08-30.md) — o degrau 1 daquele estudo (rerank por features) só rende no planejamento **se** este acoplamento existir; os dois formam uma linha só
**Continuação:** [`analise-viabilidade-devorq-2026-08-30.md`](analise-viabilidade-devorq-2026-08-30.md) — o mesmo conserto do `hub_briefing` destrava o GATE-4 do orquestrador DEVORQ
**Status:** verificado contra o código do hub, o plugin `bc-harness 0.2.0` instalado e o banco do ambiente Docker

---

## 1. Veredito

**Viável, com diff pequeno — mas o bloqueio não está no lado do harness.** O harness já tem o encaixe pronto e o hub já tem a tool. O que está quebrado é o `HubBriefingService`: ele **filtra fora justamente o conhecimento que existe para expor**.

Prova empírica, rodada contra o banco real (106 memórias):

```
hub_briefing(stack: "Laravel", description: "corrigir erro de fila no deploy")
→ known_risks: 0
```

**Zero riscos conhecidos** — num banco com 16 memórias do tipo `error`, 9 delas validadas. Duas dessas invisíveis eram:

- *"Ambiguidade SQL em Joins com Colunas de Mesmo Nome"* (`stack: SQL, PHP`)
- *"Race Condition em Selects com x-model no Alpine.js"* (`stack: Alpine.js, JavaScript, TypeScript`)

Ambas descrevem exatamente o tipo de armadilha que um planejamento Laravel/Livewire precisa antecipar. Nenhuma chegou. **Não é falta de conteúdo — é cegueira do mecanismo de leitura.**

**Ordem correta de execução:** consertar o `HubBriefingService` (§3) → ligar o passo no `/plan` (§4). Inverter a ordem entrega um harness que consulta um oráculo mudo.

---

## 2. A fundação já existente é maior do que parece

| Peça | Estado | Arquivo |
|---|---|---|
| **Lado escrita** — captura contínua | ✅ Operando (hook `PostToolUse` → `memory_ingest`) | `~/.claude/hooks/post_tool_use.sh`, `HarnessHookGenerator.php` |
| **Lado leitura** — consulta preventiva | ⚠️ Existe, mas cega (§3) | `HubBriefingService.php`, tool `hub_briefing` (`MemoryMcpServer.php:181`) |
| **Distribuição de harness** | ✅ 4 tools (`harness_paths/capture/provision/installer_script`) | `HarnessProfileService.php` |
| **Destino da evidência no planejamento** | ✅ **Slot já existe** — `planner` emite `## Risks \| Blast radius \| Mitigation \| Rollback` | `bc-harness/0.2.0/agents/planner.md:141` |
| **Grafo de conhecimento** | ✅ 188 nós / 305 arestas validadas | `KnowledgeGraphQueryService.php` |

**O que falta é uma ponte, não uma fundação:** nenhum passo do `/plan` chama o MCP do dev-memory. O `pre-fetch` (§1 do `plan.md`) lê `AGENTS.md`, `docs/agents/`, a cadeia `.spec/init/` — e nada mais.

O detalhe que decide o desenho: **o `planner` já produz uma tabela de riscos, hoje preenchida por especulação do modelo.** Existe um slot durável, num artefato durável, esperando evidência. É o encaixe mais limpo que o levantamento encontrou.

---

## 3. Por que o briefing devolve vazio — quatro defeitos

### 3.1 Só enxerga `validated` — ~20% do corpus

`HubBriefingService::validated()` exige `validation_status = VALIDATED`. Em produção: **22 validated de 110** (`STATUS.md`). No Docker: 39 de 106. Quatro em cada cinco memórias são invisíveis ao momento de planejar.

O filtro é defensável (não planejar sobre conhecimento não revisado), mas hoje é absoluto. Melhor: **incluir `pending` com peso menor e marcação explícita** (`⚠️ não validada`) em vez de omitir.

> Isso **não** reabre a questão de vazamento de segredo: a sanitização acontece no `CaptureSanitizer`, na ingestão, antes da memória existir. `pending` significa "não revisada por humano", não "não higienizada".

### 3.2 `stack LIKE` sobre string livre — o defeito que zera o resultado

`where('stack', 'like', "%{$s}%")` roda contra um campo de texto livre, sem vocabulário controlado. Os 9 erros validados do banco:

```
[PHP, jQuery, JSON, AJAX]                      PHP Notice quebrando resposta JSON em endpoint AJAX
[hermes_plugins, telegram_platform, ...]       Telegram polling conflict 409 ...
[Vite, TypeScript]                             Erro EADDRINUSE: Porta 3001 já em uso no Vite
[Vite, TypeScript, Node.js]                    Vite TypeScript build falha com "out of memory"
[Vite, TypeScript, tsconfig.json]              Erro 'Cannot find module @/...'
[SQL, PHP]                                     Ambiguidade SQL em Joins com Colunas de Mesmo Nome
[MySQL, PHP]                                   Divergência de Esquema: valorDesconto ausente
[Alpine.js, JavaScript, TypeScript]            Race Condition em Selects com x-model no Alpine.js
[devorq, chmod, Linux/Unix]                    Erro Permission Denied ao executar devorq
```

**Nenhum contém a substring "Laravel".** Um projeto Laravel planejando trabalho recebe zero — mesmo com metade dessa lista sendo diretamente aplicável. O filtro exige coincidência léxica onde o que importa é proximidade semântica.

### 3.3 O briefing não usa o retriever bom do próprio hub

`HubBriefingService` consulta `Memory::query()` com `LIKE` (`HubBriefingService.php:59,79`) e **nunca toca no `MemorySearchService`**. O hub tem busca híbrida lexical + vetorial com fusão RRF — e o caminho de leitura de maior valor (planejamento) usa a busca mais fraca que existe no código.

**Rotear o briefing pelo `search()` resolve §3.2**: similaridade vetorial não se importa que a string diga "PHP" e não "Laravel". É o conserto de maior alavancagem deste estudo, e é um diff pequeno.

> ⚠️ **Quem implementar precisa saber:** o conserto depende da **perna vetorial viva**. No ambiente Docker os 106 registros têm `embedding_model` vazio (estudo de rerank §3.4) → `search()` degrada para `lexical`, e busca lexical por "Laravel" não casa `[SQL, PHP] Ambiguidade SQL em Joins` melhor que o `LIKE` atual. Em produção a perna semântica funciona (`analise-2026-08-22` confirmou modo `semantic`). **Testar o conserto sem popular embeddings produz o mesmo zero e a falsa conclusão de que ele não funcionou.**

> 🔗 **Ligação com o estudo de rerank:** o degrau 1 de lá (re-score por `validation_status`, `recurrence_count`, `doc_validation_status`, stack, recência) faz o briefing devolver o **melhor** conhecimento, não só conhecimento relacionado — mas só se o briefing passar pelo `search()`. Os dois estudos convergem no mesmo commit.

### 3.4 O payload não carrega o que o planejador precisa

`format()` devolve `id, title, stack, recurrence, reference` — **sem `description`**. Um planejador que recebe oito títulos ou faz oito `memory_get` de ida e volta, ou planeja em cima de manchetes. Para um consumidor de planejamento isso é defeito de payload, não refinamento: incluir `description` truncada (~300–500 chars, a média real é 1.097).

---

## 4. Onde acoplar — um ponto, não quatro

| Candidato | Veredito |
|---|---|
| **Passo no `/plan`, entre §4 (architecture gate) e §5 (specifier)** | ✅ **Escolhido** |
| Hook `SessionStart` / `UserPromptSubmit` | ❌ Injeção incondicional em todo prompt é ruído; planejar é um momento discreto, não ambiente |
| Novo subagente dedicado | ❌ Custo de orquestração sem ganho — o router do `/plan` já faz probe batch e checkpoints |
| Distribuir via `harness_provision` | ↩️ Não é alternativa de arquitetura, é **entrega** — como o comando chega a uma máquina limpa (lane já prevista no roadmap) |

### O desenho

```
/plan §4 architecture gate
        │
        ▼
   §4.5 BRIEFING  ──MCP──> hub_briefing(stack, description)
        │                   └─ riscos + padrões + lições + skills (com descrição)
        ▼
   .spec/features/[slug]/BRIEFING.md          ← artefato durável, citável
        │
        ├──> specifier (§5)  → RIGID ganha restrições com origem em fato
        └──> planner   (§7)  → ## Risks deixa de ser especulação e passa a citar ids reais
```

> ⚙️ **Restrição de implementação:** `briefing()` monta **quatro** consultas por tipo (`error`, `best_practice`, `lesson` + match por descrição). Roteá-las ingenuamente pelo `search()` dispara `EmbeddingGeneratorService::generate()` **uma vez por consulta** — 4 round-trips HTTP, com timeout de 8s (MiniMax) ou 60s (Ollama) cada. Num passo síncrono do `/plan` isso colide de frente com §5.1. **Gerar o embedding da query uma única vez e reusá-lo** nas quatro (ou fazer uma busca só e particionar por tipo em PHP), com **orçamento de tempo total** para o passo — estourou, escreve o marcador de indisponibilidade e segue.

Coerente com o **handoff budget** do `plan.md` (conteúdo grande trafega como **path**, nunca inline) e com o formato dos artefatos existentes. O `BRIEFING.md` também vira registro auditável: daqui a seis meses dá para ver com que evidência aquele plano foi feito.

---

## 5. Restrições não-negociáveis

### 5.1 Não-bloqueante, e o silêncio precisa ser visível

`/plan` **tem que** completar com o hub fora do ar, token expirado ou `/api/mcp` lento. Precedente no próprio código: `EmbeddingGeneratorService::generate()` devolve `null` e a busca degrada sozinha.

Mas aqui a degradação **não pode ser silenciosa**: o `BRIEFING.md` é durável e alguém vai lê-lo depois acreditando que os riscos foram baseados em evidência. Em falha, escrever o arquivo com marcador explícito:

```markdown
> ⚠️ HUB INDISPONÍVEL — este plano foi feito SEM evidência histórica.
```

### 5.2 Escopo de token — decide se a ideia funciona ou não

O valor inteiro da proposta é **reúso cross-project**: "erros acontecidos no passado", inclusive em *outros* projetos. Um token project-bound derrota o propósito. Evidência registrada: `analise-2026-08-22.md:39` — o token do DSH enxergava **12 de 110 memórias**.

Pior que o número: o serviço **zera as skills por construção** para qualquer token project-bound — `'skills' => $visibleProjectId === null ? $this->skills($stack) : []` (`HubBriefingService.php:46`). Não é "vê menos"; é **não vê nenhuma**, em silêncio.

**O caminho de planejamento precisa de token com visão cross-project** (global ou um escopo de leitura ampliado). Sem isso, o briefing devolve o que o projeto já sabe sobre si mesmo — que é exatamente o conhecimento que não estava faltando.

> Nota de ambiente: o banco Docker tem `skills_published = 0`, então a metade "skills" do briefing é intestável localmente independente de token — mesma classe de problema do aviso em §3.3.

---

## 6. O laço que se fecha (e o débito que o trava)

Com o passo de leitura ligado, o ciclo fecha:

```
execução → hook PostToolUse → memory_ingest → curadoria → memória
    ▲                                                        │
    └──────── planejamento ← BRIEFING.md ← hub_briefing ◄────┘
```

**Caveat honesto:** o laço só gira se a curadoria acompanhar. Hoje **67 de 106** memórias estão `pending` (prod: 88 de 110) e o briefing só enxerga `validated` — o lado da escrita está correndo mais rápido que o da leitura. Isso já consta como pendência no `STATUS.md:73` ("validar na UI — 88 memórias pending"). É a intervenção operacional de maior alavancagem e não custa código: **validar o backlog**.

---

## 7. Fora de escopo (declarado)

**`memory_related` / knowledge graph** — o grafo tem 188 nós e 305 arestas validadas, e `relatedTo()` traz caminho + evidências. Mas exige uma **memória âncora**: só é útil *depois* que o briefing achou as primeiras N. É expansão natural de uma fase 2 (briefing → âncoras → vizinhança causa/solução), **não** parte do núcleo. Fica registrado como extensão, não como incógnita.

---

## 8. Riscos

| Risco | Mitigação |
|---|---|
| **Ruído no planejamento** — briefing genérico vira parágrafo ignorado | Teto rígido (5–8 itens por categoria), ordenação por relevância real (§3.3), `description` truncada |
| **Falsa confiança** — plano cita evidência que não foi lida | Toda linha do `## Risks` de origem histórica cita `id` da memória; leitor confere |
| **Vazamento entre projetos** — token amplo expõe memória de terceiro | `McpAccessPolicy` já governa; a decisão de §5.2 é consciente e deve ser registrada em ADR |
| **Acoplamento a um harness só** | O `BRIEFING.md` é markdown puro; qualquer harness (Codex, Hermes, Antigravity) consome o mesmo artefato via mesma tool |

---

## 9. Próximos passos

1. **Validar o backlog de `pending` em prod** (§6) — sem código, destrava 80% do corpus para o briefing.
2. **Consertar o `HubBriefingService`** (§3): rotear por `MemorySearchService` (**pré-requisito: embeddings populados no ambiente de avaliação** — senão o teste dá falso negativo, §3.3), embedding da query gerado uma vez só (§4), `description` truncada, `pending` admitido com marcação. É o commit que faz a ideia funcionar.
3. **Decidir o escopo do token de planejamento** (§5.2) e registrar em ADR.
4. **Ligar o passo §4.5 no `/plan`** + `BRIEFING.md` como input de `specifier`/`planner`.
5. Rodar `/bc-harness:plan` para os passos 2–4 → `.spec/features/briefing-de-planejamento/`.
