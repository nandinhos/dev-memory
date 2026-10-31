# Plano de Execução — Dev Memory Hub → Segundo Cérebro (2026-08-22)

**Objetivo:** executar, em sequência profissional com bloqueios explícitos, as Fases A–C do relatório `docs/reports/analise-2026-08-22.md`, transformando o hub em um segundo cérebro completo: operável de qualquer lugar (admin via DSH), alimentado continuamente (hooks), com ambiente reconstruível (harness) e escala preparada (busca).

**Princípio (Ponytail):** nada além do necessário. Cada passo tem **critério de aceite verificável por output observado** — nenhuma etapa é considerada pronta por narração. Todo comando abaixo foi conferido contra o código-fonte nesta sessão.

**Perfil de risco:** conservador — produção primeiro protegida, mudanças de código em branch `dev` + PR + testes, deploy só via Jarvis Forge após gate humano.

---

## 0. Mapa de Bloqueios (dependências)

```
A1 token global ──┬──► A2 harness capture/provision
                  └──► B1 hooks (precisa token p/ ingest)
A2 ──► validação de adapters reais
B2 (indexar RecurrenceScorer) ──► independe de A; pode rodar em paralelo
B3 (search UI) ──► depende de B2 concluído (reusa MemorySearchService já existente, sem B2)
C1 (laravel/mcp) ──► só após B2/B3 estabilizados (não empilhar mudança de transporte com mudança de busca)
C2 (scheduler+alerta) ──► independe; só precisa acesso VPS
C3 (rotacionar segredos) ──► executa por último (não rotacionar token global no meio das Fases A/B)
```

**Regra de ouro:** A1 antes de qualquer ingestão/hook; C3 por último. Entre fases, **gate humano** (checkpoint de verificação).

---

## FASE A — "Operar como dono" (acesso + primeiro provisionamento + hardening)

### A1. Emitir token global de admin e religar o DSH

**Objetivo:** o DSH passa a enxergar as 110 memórias, 17 projetos e perfis de harness (hoje vê 12/0).

**Bloqueios:**
- 🔒 **A UI `/admin/tokens` NÃO cria token global** (verificado: `ApiTokens::create` → `ApiToken::issue(..., global: false)`). Caminho: `tinker` no servidor de produção.
- 🔒 Exige usuário com `is_admin=true` (confirmar com `User::where('is_admin', true)->get()`).
- 🔒 O token global exibido 1× — copiar sem colar em chat (regra operacional do STATUS.md).

**Passos:**
1. SSH na VPS (ou terminal Jarvis Forge) → `cd /var/www/devmemory.fssdev.com.br/current`.
2. `php artisan tinker` (interativo — **evitar `--execute`** para não vazar o plaintext no histórico do shell):
   ```php
   $u = App\Models\User::where('is_admin', true)->first();
   [$t, $plain] = App\Models\ApiToken::issue($u, 'dsh-global', null, null, true);
   echo $plain; // copie agora — não será exibido novamente
   ```
3. Atualizar `~/.dsh/.env` no DSH → `DEV_MEMORY_API_KEY=<novo_plaintext>`.
4. **Reiniciar a sessão/processo do DSH** para recarregar a env var (o processo atual leu o `.env` no boot).

**Critério de aceite (verificável):**
- `mcp__dev-memory__memory_stats` → `total` = **110+** (não 12).
- `mcp__dev-memory__harness_list` → lista perfis existentes (≠ vazio, se houver).
- O token antigo (project-bound) **revogado** em `/admin/tokens` ou marcado como obsoleto.

**Estimativa:** 20–30 min. **Rollback:** revogar o token em `/admin/tokens`; restaurar `DEV_MEMORY_API_KEY` antigo.

---

### A2. Capturar o primeiro perfil de harness e validar provisionamento

**Objetivo:** provar o ciclo "captura → provisiona → reconstrói" com o próprio ambiente (promessa central do segundo cérebro).

**Bloqueios:**
- 🔒 A1 concluído (token global enxerga perfis).
- 🔒 Máquina de teste limpa disponível (ou WSL/container descartável) para validar o instalador sem risco ao ambiente real.
- 🔒 `jq` instalado na máquina de origem para o hook (passo B1) — não para A2.

**Passos:**
1. Na máquina de origem: `mcp__dev-memory__harness_paths(harness: "claude-code")` → ler os arquivos que existirem.
2. `mcp__dev-memory__harness_capture(harness: "claude-code", files: [{path, content}], name: "default")` → conferir resposta `files_with_redactions` (segredos redigidos).
3. `mcp__dev-memory__harness_provision(harness: "claude-code")` → revisar `steps` + `notes` (had_secrets).
4. Na máquina limpa: `mcp__dev-memory__harness_installer_script(harness: "claude-code")` → aplicar o script (interativo; `--force` só se intencional) OU baixar em `/install/harness/claude-code` (admin) e `bash install.sh`.
5. Verificar que a máquina limpa agora tem `.mcp.json` com o dev-memory conectado (bootstrap recursivo) e que `mcp:serve` local responde `tools/list`.

**Critério de aceite:**
- `harness_list` mostra o perfil com `version` ≥ 1.0.0 e `files` contados.
- Na máquina limpa: `~/.claude/CLAUDE.md` e `.mcp.json` presentes; `memory_stats` local via stdio responde.
- Script aborta (não sobrescreve) ao rodar 2ª vez sem `--force` — idempotência.

**Estimativa:** 1–2 h. **Rollback:** remover perfil via `/admin/harness` (ou DB); deletar arquivos provisionados na máquina de teste.

---

### A3. Endurecimento rápido (HSTS + higiene do repo)

**Bloqueios:** acesso ao painel Cloudflare (edge) para HSTS; acesso Git para commit/push.

**Passos:**
1. Cloudflare → SSL/TLS → Edge Certificates → **HSTS: ativar** (max-age 31536000, includeSubdomains, preload opcional).
2. Remover `cookies.txt` do repo: `git rm --cached cookies.txt` (ou deletar) + adicionar `cookies.txt` ao `.gitignore` → commit → push (dev → PR → main).

**Critério de aceite:**
- `curl -sI https://devmemory.fssdev.com.br/ | grep -i strict` → `strict-transport-security` presente.
- `git status` limpo em relação a `cookies.txt`.

**Estimativa:** 15 min. **Rollback:** desativar HSTS no Cloudflare; restaurar arquivo se necessário.

---

## FASE B — "Segundo cérebro de elite" (captura contínua + escala + busca na UI)

### B1. Captura contínua via hooks nos harnesses

**Objetivo:** conhecimento entra sem fricção durante o trabalho (bug resolvido → `memory_ingest` automático).

**Bloqueios:**
- 🔒 A1 (token com direito de escrita/ingest no projeto alvo).
- 🔒 `jq` instalado nas máquinas que rodam o hook (o script gerado usa `jq -n` — verificado no `HarnessHookGenerator`).
- 🔒 Decidir o **projeto MCP** a que o hook pertence: o token do hook deve ser project-bound do projeto que ele observa (ex.: `dev-memory-laravel`), senão `memory_ingest` associa ao projeto errado (proveniência vem do token, não do campo `project`).
- 🔒 O hook precisa receber conteúdo via stdin (o script lê `cat -`): alinhar com a integração de cada harness (PostToolUse/Stop) para **passar o resumo do evento**.

**Passos:**
1. Emitir token project-bound p/ o projeto de teste: UI `/admin/tokens` (nome, expiração) — **ou** via MCP com token global.
2. `mcp__dev-memory__harness_hook_script(harness: "claude-code", mcp_url: "https://devmemory.fssdev.com.br/api/mcp")` → obter script; inserir o token no header OU usar o placeholder e exportar `DEV_MEMORY_API_KEY` no ambiente.
3. Instalar em `~/.claude/hooks/post_tool_use.sh` (+`chmod +x`) e configurar no `settings.json` do Claude Code.
4. Teste de fumaça: disparar um evento, conferir `mcp__dev-memory__memory_list`/captures na UI `/admin/captures` e o status da curadoria.
5. Repetir para 1 harness extra (ex.: Codex) se o 1º validar.

**Critério de aceite:**
- Um evento real (ex.: bug resolvido) aparece como **capture** na UI (status `sanitized` → `curated`/`memory_created`).
- O hook **não bloqueia** o harness (rodou em background — `&` no script).
- `memory_ingest` com o mesmo conteúdo 2× → `deduplicated: true` (idempotência).

**Estimativa:** 1 sessão (~2–3 h). **Rollback:** remover o hook do `settings.json`/`~/.claude/hooks`; revogar o token.

---

### B2. Indexar o RecurrenceScorer (fim do full-scan em RAM)

**Objetivo:** dedup de capturas sem carregar todas as memórias em memória (O(n) hoje — verificado em `RecurrenceScorer::findMatch`); preparar o hub para N projetos.

**Bloqueios:**
- 🔒 Mudança de código → branch `dev`, testes, PR, gate humano → deploy.
- 🔒 O top-K de candidatos deve usar a **busca híbrida existente** (`MemorySearchService`) ou o **grafo** (`memory_related`); manter os mesmos floors (TEXT≥0.55 / TOTAL≥0.50) para não mudar o comportamento de dedup sem métricas.
- 🔒 Rodar **regressão** no teste `RecurrenceScorerTest` + `CurateCaptureJobTest` (os pares de duplicata reais calibram os floors).

**Passos:**
1. Em `dev`: extrair candidatos por busca híbrida (title+summary do draft) com `limit ≈ 20–30` (espaço de candidatura), mantendo o escopo `project_id`.
2. Aplicar o score composto **apenas** sobre os candidatos (não sobre `Memory::all()`).
3. Testes: manter 100% verdes (259+); adicionar caso "candidato fora do top-K não vira match".
4. PR → CI verde → merge → deploy via Jarvis Forge → `php artisan optimize` (hook já faz).

**Critério de aceite:**
- `./vendor/bin/pint --test` passed; `php artisan test` ≥ 259 passed.
- Benchmark opcional: captura em prod com 110 memórias não degrada (duração do job < antes, ou estável).
- Nenhuma mudança de contrato MCP (respostas idênticas).

**Estimativa:** 1 sessão (~3–4 h). **Rollback:** revert do PR; redeploy do commit anterior (releases tornam isso trivial).

---

### B3. Search global na UI (híbrida, não LIKE)

**Objetivo:** a busca rica (lexical+vetorial+RRF) que só existe via MCP chega ao humano na navegação.

**Bloqueios:**
- 🔒 `MemorySearchService::search` já existe e é usado pelo MCP — a UI (`MemoryList`) usa `LOWER(title) LIKE` hoje (verificado). Reuso, sem novo motor.
- 🔒 Expor `search_mode` e `rank_score` na view (para o usuário saber quando é híbrido vs lexical).
- 🔒 Deploy via CI/PR como B2.

**Passos:**
1. Em `MemoryList::render`: se `$search` preenchido, delegar a `MemorySearchService->search($search, $filters, limit)` e mesclar com a paginação existente (ou trocar paginação por "carregar mais").
2. Badge `search_mode` no cabeçalho de resultados (HYBRID/SEMANTIC/LEXICAL/FILTER).
3. Testes: `MemoryListFilterTest` + novo caso de busca híbrida na view; manter filtros persistidos na query string.

**Critério de aceite:** digitar "Laravel deploy" na UI retorna os mesmos 3 resultados do MCP (mesma ordem); badge mostra o modo.

**Estimativa:** 1/2–1 sessão. **Rollback:** revert do PR.

---

## FASE C — Maturidade de plataforma (após B estabilizar)

### C1. Avaliar migração p/ `laravel/mcp` (Streamable HTTP oficial)

**Bloqueio:** 🔒 só iniciar com A/B verdes e um sprint de estabilidade entre eles (não empilhar transporte + busca). Requer decisão de arquitetura documentada (novo ADR).

**Passos:** spike em `dev` → comparar 19 tools atuais vs `laravel/mcp` → mapa de equivalência → ADR → implementar em PR separado → teste completo do MCP (HTTP + stdio + isolamento).

**Critério de aceite:** `McpHttpTest`/`McpStdioTest`/`McpProjectIsolationTest` verdes no novo transporte; 19 tools com os mesmos nomes/contratos.

**Estimativa:** 1–2 sprints. **Rollback:** manter o transporte manual (ambos coexistem na mesma classe `MemoryMcpServer`).

### C2. Scheduler na VPS + olho na fila

**Bloqueio:** 🔒 acesso VPS/Jarvis Forge para confirmar cron `schedule:run` (o scheduler de auto-cura `memory:process-captures --retry-failed` hourly depende dele — verificado em `routes/console.php`).

**Passos:**
1. Confirmar/instalar cron: `* * * * * cd /var/www/devmemory.fssdev.com.br/current && php artisan schedule:run >> /dev/null 2>&1`.
2. Rotina simples de monitoramento: alerta quando `captures` com status `failed` > 0 (ex.: comando ad-hoc + webhook, ou página admin).

**Critério de aceite:** `php artisan schedule:list` na VPS mostra `memory:process-captures --retry-failed` hourly; log de capturas FAILED → recuperadas automaticamente.

**Estimativa:** 30 min. **Rollback:** remover o cron.

### C3. Rotacionar segredos do backlog de segurança (por último)

**Bloqueio:** 🔒 **não** rotacionar o token global novo (A1) antes de A2/B1 prontas; executar em janela de manutenção curta.

**Passos:** (a) reemitir tokens project-bound das integrações com escopo mínimo; (b) mover credenciais de prod p/ env vars (wrapper); (c) auditar `~/.config/opencode/opencode.json`, `~/.zshrc`, `.mcp.json`; (d) revogar tokens antigos após confirmação de que nada mais os usa.

**Critério de aceite:** nenhuma credencial real em arquivos de config capturáveis; `git grep` por padrões de chave no repo retorna vazio.

**Estimativa:** 1 sessão. **Rollback:** reemissão reversível (revogar novos, restaurar antigos apenas se ainda persistidos — mitigar com backup de hashes).

---

## Sequência Recomendada + Gates

| Ordem | Passo | Gate humano após | Verificação do gate |
|---|---|---|---|
| 1 | **A1** token global + DSH | ✅ sim — não avançar sem stats=110 | `memory_stats` no DSH |
| 2 | **A2** harness capture/provision | ✅ sim — prova do ciclo | provision numa máquina de teste |
| 3 | **A3** HSTS + higiene | opcional | headers + git status |
| 4 | **B1** hooks | ✅ sim — 1 captura real na UI | capture `curated` |
| 5 | **B2** indexar RecurrenceScorer | ✅ sim — CI verde | testes + job estável |
| 6 | **B3** search UI | ✅ sim — paridade MCP/UI | mesma query, mesmos resultados |
| 7 | **C2** scheduler/alerta | ✅ sim | schedule:list |
| 8 | **C1** laravel/mcp (spike) | ✅ sim — ADR aprovado | spike documentado |
| 9 | **C3** rotacionar segredos | ✅ sim — janela de manutenção | grep limpo |

**Paralelizável:** A3 e C2 não dependem de nada (podem entrar na 1ª janela). B2 pode rodar em paralelo com A2/B1 (mudança isolada em `dev`).

---

## Riscos e Mitigações

| Risco | Prob. | Impacto | Mitigação |
|---|---|---|---|
| Token global vazado ao copiar | média | alto | Tinker interativo, nunca chat; revogação imediata; C3 ao final |
| Hook envia conteúdo sem contexto (stdin vazio) | alta | baixo | Script sai com `exit 0` se `CONTENT` vazio (verificado); testar integração por harness |
| Mudança de dedup altera comportamento em prod | média | médio | Mesmos floors; testes com pares reais; deploy com release rollback |
| `laravel/mcp` não cobre 19 tools | média | médio | Spike antes de decidir; transporte manual permanece |
| Scheduler ausente na VPS | alta (hoje) | médio | C2 confirma/instala cron; sem ele, auto-cura não roda |
| Rotação de segredos quebra integração ativa | baixa | alto | C3 por último; janela de manutenção; reemissão com teste de cada integração |

---

## Definição de Pronto (DoD) da rodada

1. DSH opera o hub com **visão admin** (stats = 110+, harness_list ≠ vazio) — A1 ✅.
2. **1 perfil de harness** capturado e **replicado** numa máquina limpa — A2 ✅.
3. **≥1 hook** de captura contínua produzindo captures reais curadas — B1 ✅.
4. `RecurrenceScorer` sem full-scan; **259+ testes verdes**; Pint passed — B2 ✅.
5. **Busca híbrida na UI** com badge de modo — B3 ✅.
6. **Scheduler confirmado** na VPS; HSTS ativo; repo higienizado — A3/C2 ✅.
7. Decisão registrada sobre `laravel/mcp` (ADR) — C1 ✅ (spike).
8. **Segredos rotacionados** e verificação de repo limpa — C3 ✅.

---

*Plano gerado em 2026-08-22 a partir do relatório `docs/reports/analise-2026-08-22.md`. Todos os comandos e bloqueios conferidos contra o código-fonte e o MCP de produção na mesma sessão.*
