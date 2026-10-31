# Análise de Viabilidade — DEVORQ acoplado ao dev-memory

**Data:** 2026-08-30
**Objeto:** acoplar o **debug sistemático** e a **aquisição de lições aprendidas** do DEVORQ ao pipeline de captura → curadoria → preparação de conhecimento do hub, para que a codificação seja alinhada por evidência e não por memória do desenvolvedor
**Fonte analisada:** `nandinhos/devorq` v4.1.0 (`/home/nandodev/projects/devorq`) — `lib/debug.sh`, `lib/lessons/*`, `lib/gates.sh`, `lib/compact.sh`
**Relação:** fecha a trilogia — [rerank](analise-viabilidade-rerank-2026-08-30.md) (qualidade do retriever) → [harness de planejamento](analise-viabilidade-harness-planejamento-2026-08-30.md) (leitura no planejar) → **este** (escrita e leitura no executar)
**Status:** verificado contra o código dos dois lados e contra as lições reais em disco

---

## 1. Veredito

**Viável, e é a integração de maior retorno das três.** O motivo cabe em um parágrafo:

O `STATUS.md` registra que a captura contínua já existente (hook `PostToolUse` → `memory_ingest`) teve eventos **descartados pela curadoria por baixa confiança — "texto genérico"**. Enquanto isso, a Phase 4 do `devorq debug` produz exatamente o oposto de texto genérico — **mensagem de erro exata, tipo de erro detectado, hipótese explícita e fix validado pelo próprio desenvolvedor** — e termina assim (`lib/debug.sh:215-217`):

```bash
debug::pass "Fix implementado com sucesso!"
debug::info "Registre a lição aprendida:"
debug::info "  devorq lessons capture '<título>' '<problema>' '<solução>'"
```

**Uma sugestão impressa no terminal.** O material mais bem estruturado que o ecossistema produz, no exato momento em que ele existe, com todas as variáveis ainda vivas em memória (`error_msg`, `error_type`, `hypothesis`) — e o fluxo termina pedindo que o humano digite tudo de novo. As evidências em disco mostram o resultado dessa aposta: **7 projetos com o diretório de lições criado e vazio**.

Essa é a "captura automática" pedida. Ela não precisa ser inventada: precisa ser **ligada**.

---

## 2. O problema de fundo: dois pipelines paralelos fazendo a mesma coisa

O DEVORQ não é complementar ao dev-memory — ele **reimplementa em Bash** o que o hub já faz em Laravel, com uma versão mais fraca de cada componente:

| Etapa | DEVORQ | dev-memory |
|---|---|---|
| Captura | `lessons::capture` → JSON em `.devorq/state/lessons/captured/` | `memory_ingest` → `Capture` + `CaptureSanitizer` (segredos redigidos) |
| Dedup | `lessons::_fuzzy_check` (similaridade textual) | `RecurrenceScorer` (candidatura híbrida top-K + floors) |
| Validação documental | `lessons::validate` (Context7, GATE-6) | `DocumentationValidator` + `Context7Client` + verdict persistido |
| Curadoria | ausente — o que o dev digitou é o que fica | `AnthropicCurationEngine` (MiniMax, saída estruturada + repair) |
| Busca | `grep -l -iF` literal sobre arquivos JSON | `MemorySearchService` (lexical + vetorial + RRF) |
| Skills | `lessons::compile` (infere por tags) | `SkillGroupProposer` → `SkillCompiler` → `SkillPublisher` (git-versionado) |
| Distribuição | `lessons::sync_vps` (scp do JSON) | MCP remoto (HTTP + token, isolamento por projeto) |

**A recomendação estrutural que sai daí:** o DEVORQ deve manter o que só ele tem — **os gates, o contexto de execução e o momento** — e delegar armazenamento, curadoria, busca e compilação ao hub. Ele é o **sensor**; o hub é a **memória**.

---

## 3. Achados verificados

### 3.1 O GATE-4 é decorativo

O gate existe para impedir retrabalho. Seu nome (`lib/gates.sh:8`) promete:

> `GATE-4 (BLOQUEANTE): Lessons Reviewed — devorq lessons search encontrou lições relevantes`

A implementação (`lib/gates.sh:345-368`):

```bash
found=$(find "$lessons_dir" -maxdepth 1 -name "*.json" -type f | wc -l)
if [ "$found" -gt 0 ]; then
    gate::pass 4 "Lessons Reviewed — $found lição(ões) capturada(s)"
```

**Nunca chama `lessons::search`. Nunca compara com a intent da tarefa.** Conta arquivos numa pasta local e aprova. Um projeto com uma lição irrelevante de 2026 passa; um projeto com dez lições diretamente aplicáveis em *outro* diretório reprova. O gate mais importante para "evitar retrabalho" verifica a existência de uma pasta.

*(Nota: o cabeçalho declara BLOQUEANTE, o comentário da função diz NÃO BLOQUEANTE e o código retorna 0 nos dois caminhos — divergência de documentação a corrigir junto.)*

### 3.2 O conhecimento está em silos, um por projeto

Lições em disco, por diretório:

```
12  projects/devorq                    .devorq/state/lessons/captured   (.json)
 6  projects/eventos_control_new       .devorq/state/lessons/captured   (.json)
 2  projects/metropole/app             .devorq/state/lessons/captured   (.json)
 2  projects/clickup                   .devorq/state/lessons/captured   (.json)
 1  projects/filament-design-kit       .devorq/state/lessons/captured   (.json)
 6  projects/eventos_control_new       .devorq-auto/lessons             (.md)  ← outro formato
 0  + 7 projetos com o diretório criado e vazio
```

**29 peças, 6 silos e 2 formatos incompatíveis** — os 6 arquivos `.md` em `.devorq-auto/lessons` não passam pelo pipeline `lessons::*` e não são vistos nem pelo próprio DEVORQ. Zero visibilidade cruzada. `DEVORQ_LESSONS_DIR` aponta para `${PWD}/.devorq/state/lessons` — por construção, o que se aprendeu no `eventos_control_new` é invisível ao `metropole`. É exatamente o problema que o hub existe para resolver, e a razão de o GATE-4 não conseguir evitar retrabalho nenhum.

### 3.3 A captura manual produz dados ruins — medido, não suposto

Varredura dos 23 arquivos:

| Sintoma | Incidência |
|---|---|
| `problem` == `"--problem"` (a flag da CLI virou o valor) | **11 de 23 (48%)** |
| `stack` == `"unknown"` | **21 de 23 (91%)** |
| `tags` == `[]` | maioria |

Quase metade das lições tem o campo *problema* destruído por erro de digitação de CLI, e 91% não têm stack. Pelo estudo #2 §3.2, `stack: unknown` significa **permanentemente invisível ao `hub_briefing`**. O pipeline aprova (`validated: true`, `approved: true`, validador `manual-nando`) e compila lições cujo campo central diz `--problem`.

### 3.4 A Phase 2 do debug pergunta ao humano o que o hub sabe

```
── PHASE 2: Pattern Analysis ──
  1. Existe código similar que funciona? Onde?
  2. O que é diferente entre o que funciona e o que não?
```

É literalmente uma consulta de recuperação de conhecimento, dirigida à memória do desenvolvedor. `memory_search(error_msg)` responde à primeira pergunta com evidência de outros projetos — e responde melhor quanto mais o hub tiver sido alimentado pelo item A abaixo.

### 3.5 O handoff entre LLMs não carrega conhecimento nenhum

`compact::generate` (`lib/compact.sh:13-70`) produz:

```json
{"handoff": {"project", "stack", "intent", "gates_completed",
             "pending_gates", "untracked_files", "timestamp"}}
```

Puro estado mecânico. O artefato que existe para dar contexto ao próximo LLM não carrega **um único risco conhecido, padrão aprovado ou erro já cometido**. É o ponto exato do "preparar dados e informação para uma codificação mais alinhada e precisa" — hoje vazio de conteúdo.

### 3.6 A intenção de hub já está no código — declarada e morta

`lib/lessons.sh:29-30`:

```bash
DEVORQ_HUB_HOST="${DEVORQ_HUB_HOST:-}"
DEVORQ_HUB_PORT="${DEVORQ_HUB_PORT:-5432}"
```

**Porta 5432 — PostgreSQL, exatamente o banco do dev-memory em produção.** As duas variáveis não são referenciadas em nenhum outro ponto do repositório (`grep -rn "DEVORQ_HUB"` → só essas duas linhas): o `lessons::sync_vps` que existe usa `DEVORQ_VPS_HOST` e `scp` por SSH, não elas.

Ou seja: **o DEVORQ já previu um hub central de lições e nunca o implementou.** Nenhuma referência ao dev-memory em `lib/` ou `bin/`. Esta proposta não introduz um conceito estranho ao orquestrador — ela completa um que ficou pela metade.

---

## 4. Os quatro acoplamentos — e a ordem correta

| # | Acoplamento | Onde | Depende de | Quando |
|---|---|---|---|---|
| **A** | **Escrita automática** — fim da Phase 4 → `memory_ingest` | `lib/debug.sh:215` | nada | **Agora** |
| **B** | **Leitura no debug** — Phase 1/2 → `memory_search(error_msg)` | `lib/debug.sh` Phase 2 | nada (busca já funciona em prod) | **Agora** |
| **C** | **GATE-4 com dentes** — `hub_briefing(stack, intent)` em vez de `wc -l` | `lib/gates.sh:345` | **estudo #2 §9 passo 2** | Depois |
| **D** | **Handoff enriquecido** — briefing dentro do `handoff.json` | `lib/compact.sh:13` | C | Por último |

### A — a captura que já deveria existir

No fim da Phase 4, com `error_msg`, `error_type`, `hypothesis` e o fix em mãos, montar um `content` estruturado e despachar. O payload é de altíssima qualidade justamente porque o workflow **obriga** a causa raiz antes do fix ("Regra de Ouro: NO FIXES WITHOUT ROOT CAUSE INVESTIGATION FIRST") — a curadoria do hub recebe erro + causa + solução, não um `git diff` solto.

### B — o debug consulta o passado antes de investigar

Entre a Phase 1 e a Phase 2: consultar o hub. Se o erro já aconteceu, o dev vê a causa raiz e o fix anteriores **antes** de gastar uma hora reinvestigando. É a resposta direta a "evitar retrabalho com erros acontecidos no passado", e é o acoplamento mais barato de todos.

> 🎯 **Consultar pela assinatura do erro, não pela mensagem crua.** `$error_msg` é majoritariamente boilerplate — caminhos absolutos, números de linha, frames de stack — que dilui o embedding e enche a busca lexical de ruído. O `debug.sh` **já** classifica em `error_type` (`syntax`/`missing_dep`/`permission`/`missing_file`/`failure`/`generic_error`); a query deve combinar esse tipo com o trecho distintivo da mensagem. Vale validar o formato da query contra o golden set do estudo de rerank antes de fixar.

### C — o gate que hoje conta arquivos

`hub_briefing(stack, intent)` no lugar do `find | wc -l`, com o resultado impresso e registrado. **Bloqueado no estudo #2**: ligar o GATE-4 ao `hub_briefing` de hoje troca um gate decorativo local por um gate decorativo remoto — e pior, com aparência de integrado. Consertar o serviço primeiro.

---

## 5. Restrições que quebram a integração se ignoradas

### 5.1 `set -euo pipefail` mata o orquestrador

`lib/debug.sh:14` e todos os módulos `lessons/*.sh` usam `set -euo pipefail`. **Um `curl` ao hub retornando não-zero aborta o workflow de debug no meio** — e o desenvolvedor perde a investigação em andamento.

O precedente correto já existe no próprio hub (`HarnessHookGenerator`): despacho em background com saída descartada.

```bash
curl -s --max-time 5 ... >/dev/null 2>&1 || true    # escrita: dispare e esqueça
resposta=$(curl -s --max-time 5 ... || true)        # leitura: degrade para vazio
```

**Nenhuma chamada ao hub pode ser capaz de derrubar o DEVORQ.** Vale inclusive para B: hub fora do ar → o debug segue como hoje.

### 5.2 `memory_ingest` não tem campos estruturados — e isso está certo

O schema aceita apenas `content`, `source`, `trigger`, `project` (`MemoryMcpServer.php:192-205`). Não há `stack`, `type` nem `severity`.

Isso **não** é um bloqueio: é o desenho. O `AnthropicCurationEngine` extrai o `LessonDraft` (`title`, `summary`, `problem`, `root_cause`, `solution`, `category`, `technologies`, `evidence`, `risks`, `confidence`) do conteúdo bruto, e `CurateCaptureJob:86` deriva o `stack` de `technologies`. Ou seja: **mandar prosa estruturada e deixar a curadoria tipar é o caminho suportado** — e resolve o `stack: unknown` de §3.3 na origem.

### 5.3 Fronteira de segredo: o trânsito muda o problema

`lessons::capture` guarda o conteúdo **cru**, por decisão consciente e documentada no código ("sanitizar aqui destruía código/comandos nas lições — justamente o conteúdo mais valioso"). Correto para um arquivo local.

Mandar esse mesmo conteúdo para um hub remoto é outra história: **saída de debug é exatamente onde moram connection strings, tokens e variáveis de ambiente**. O `CaptureSanitizer` do hub redige — mas **server-side, depois do trânsito**. Decidir explicitamente: scrub client-side antes do envio, ou aceitar o trânsito como confiável (HTTPS + token) e registrar a decisão. Não deixar implícito.

### 5.4 Token cross-project (herdado do estudo #2 §5.2)

O valor inteiro é reúso entre projetos. Token project-bound faz o DEVORQ do `metropole` continuar sem enxergar o que o `eventos_control_new` aprendeu — que é o problema original com mais passos.

---

## 6. Migração dos silos — é um upgrade de dados, não um `INSERT`

As 23 lições `.json` (mais os 6 `.md` de §3.2, em conversão) entram por `memory_ingest` e **passam pela curadoria**, que:

- **preenche o `stack`** dos 21 `unknown` a partir do conteúdo (`technologies` → `buildStack`);
- classifica o `category` no `MemoryType` correto;
- extrai `root_cause` e `evidence` que o JSON do DEVORQ não tem;
- deduplica contra o corpus existente via `RecurrenceScorer`;
- sanitiza segredos.

**Triagem obrigatória antes:** as **11 lições com `problem: "--problem"`** não têm o campo problema — o `solution` de várias delas carrega a narrativa inteira e pode sustentar a curadoria, mas isso precisa ser conferido caso a caso, não ingerido em lote às cegas. Ingerir 23 e descobrir depois que 11 viraram memórias truncadas é criar dívida no hub.

---

## 7. Divisão por repositório

| Lado | Repositório | Mudanças |
|---|---|---|
| **DEVORQ** | `nandinhos/devorq` | A (`lib/debug.sh` Phase 4), B (`lib/debug.sh` Phase 2), C (`lib/gates.sh` GATE-4), D (`lib/compact.sh`), config `DEVORQ_HUB_URL`/token, corrigir divergência BLOQUEANTE/NÃO-BLOQUEANTE do GATE-4 |
| **dev-memory** | este repo | Consertar `HubBriefingService` (estudo #2), avaliar `trigger: debug_root_cause` como sinal de confiança na curadoria, migração triada dos 23 silos |

Nenhuma mudança no hub é pré-requisito de **A** e **B** — os dois primeiros acoplamentos são PRs exclusivamente no DEVORQ.

---

## 8. Riscos

| Risco | Mitigação |
|---|---|
| **Hub derruba o orquestrador** (§5.1) | `\|\| true` + `--max-time` + background; teste explícito com hub offline |
| **Vazamento de segredo em trânsito** (§5.3) | Decisão registrada; scrub client-side ou aceite explícito |
| **Enxurrada de captura** — todo debug vira memória | Só capturar em Phase 4 **bem-sucedida** (fix confirmado); `RecurrenceScorer` já deduplica; a curadoria já descarta baixa confiança |
| **Backlog de curadoria** | O gargalo do estudo #2 §6 (67/106 `pending`) piora com mais volume entrando — validar o backlog **antes** de ligar A |
| **Divergência entre os dois pipelines** | Enquanto os dois coexistirem, `lessons::capture` local permanece como fallback offline; não remover antes do hub provar confiabilidade |

---

## 9. Próximos passos

1. **Triagem das 11 lições com `problem` corrompido** (§6) — decidir recuperar ou descartar antes de qualquer migração.
2. **Acoplamento B** (leitura no debug Phase 1/2) — PR no DEVORQ, não bloqueado por nada, retorno imediato.
3. **Validar o backlog de curadoria** (67/106 `pending` — estudo #2 §6) — **gate obrigatório antes de A**: ligar captura automática sobre um backlog represado só aumenta a fila.
4. **Acoplamento A** (escrita automática no fim da Phase 4) — PR no DEVORQ.
5. **Migrar os silos** para o hub via `memory_ingest`, em lote triado.
6. **Consertar o `HubBriefingService`** (estudo #2 §9) — destrava C e D.
7. **Acoplamentos C e D** — GATE-4 com evidência real e handoff enriquecido.
