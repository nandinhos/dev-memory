# STATUS — Dev Memory Hub

**Atualizado:** 2026-08-22 · **Saúde local:** 266 testes verdes / 722 asserções, 1 skip (gate PostgreSQL local) · **Estado:** Sprints 1–3 + Ondas 1, 2, 3, 4, 5, 6, 7, 8 (parcial) concluídas — `RecurrenceScorer` indexado via `MemorySearchService` (top-K); busca híbrida na UI com badge de modo; DSH admin ativo; **HSTS ativo**; perfil `claude-code` v1.0.0 capturado + instalador idempotente validado; **hook de captura contínua** + projeto `dev-memory-laravel` + token project-bound; ADR 0002 (migração futura para `laravel/mcp`); **2 tokens órfãos revogados** (`Projeto-Eventos-Control`, `escavacao-2026-08-13`); 145 memórias (17 projetos, 11 skills publicadas); scheduler + workers + health check operacionais na VPS

Fonte única de verdade do estado do projeto. Para a visão futura, ver [`docs/roadmap.md`](roadmap.md).

---

## O que é hoje

Hub de conhecimento **autenticado** + **servidor MCP remoto**: captura, cura, valida e reutiliza aprendizados de desenvolvimento, acessível de qualquer projeto via MCP tokenizado. Backend de curadoria completo (P1–P6 + F6), UI neo-brutalista para administração, MCP como único caminho programático.

## Arquitetura

```
      qualquer projeto / IDE / agente
        (Claude Code, Codex, ...)
                    │  MCP: stdio (local) · HTTP (remoto, Bearer token)
                    ▼
   ┌────────────────────────────────────────────────────────┐
   │  DEV-MEMORY HUB                                         │
   │                                                        │
   │  UI Livewire "neo" (auth)      MemoryMcpServer (18 tools)│
   │   ├ Dashboard / Memórias        ├ leitura   (list/search/get/related/stats)
   │   ├ Admin: Captures             ├ escrita   (create/update/validate/
   │   │        Skill Groups         │            promote/delete✋)
   │   │        Skills                └ inteligência (hub_briefing, ingest)
   │   └ MCP Tokens                                          │
   │                                                        │
   │  Pipeline de curadoria (fila)                          │
   │   capture → sanitize → curate(MiniMax) → policy →      │
   │   recurrence → doc-validate(Context7) → group →        │
   │   compile → publish(git)                               │
   │                                                        │
   │  PostgreSQL + pgvector + grafo · Redis · repo de skills│
   └────────────────────────────────────────────────────────┘

   ✋ = ação destrutiva com confirmação em duas fases
```

## Capacidades (o que funciona)

- ✅ **Embeddings consistentes** — um espaço canônico `provider/model` por ambiente, dimensão estrita, hash versionado e busca vetorial isolada por modelo
- ✅ **Busca híbrida verdadeira** — full-text PostgreSQL (`tsvector` + GIN) e pgvector combinados por Reciprocal Rank Fusion; fallback lexical quando o provedor não responde
- ✅ **Knowledge graph PostgreSQL** — nós, arestas e evidências governadas; projeção determinística de stack; consulta explicável e isolada por escopo via `memory_related`, sem equivalência entre `project_id` nulo
- ✅ **Ciclo de maturidade** — `MemoryMaturity` (workaround → provisional → recommended → canonical → consolidated) com políticas de transição governadas
- ✅ **Autenticação** — login neo, rotas protegidas, `memory:make-admin`
- ✅ **Gestão de memórias** — CRUD, filtros, validação, promoção a global
- ✅ **Pipeline de curadoria** — ingestão imutável, sanitização, curadoria MiniMax (structured output + reparo), política de promoção, recorrência composta
- ✅ **Validação documental** — Context7 (RAG fundamentado), auto-validação só de `confirmed` ≥ 0.8
- ✅ **Skills** — agrupamento por IA, compilação com rastreabilidade de fonte, publicação git versionada
- ✅ **MCP remoto** — 18 tools, HTTP + stdio; token comum vinculado a um projeto, com leitura limitada ao projeto e a memórias globais; token global é exclusivo de administrador
- ✅ **Consulta preventiva** — `hub_briefing` antes de implementar
- ✅ **Provisionamento de harness (Claude Code)** — sobe a config do ambiente (sanitizada) e replica em máquina limpa via MCP (`harness_capture/provision`, página **HARNESS**, `harness:capture-local`); perfis MCP pertencem ao emissor do token
- ✅ **Segurança** — sem credenciais hardcoded, confirmação de ações destrutivas, sem API aberta, segredos redigidos na captura de config e instalador HTTP restrito a administrador autenticado

## Dados atuais

| | |
|---|---|
| Memórias em produção | 110 (17 projetos; 88 pending, 22 validated) |
| Banco local validado | PostgreSQL 16 + pgvector 0.8.2; migrations aplicadas; fronteira MCP (batch 3); dev com 5 memórias do Tier 4 (sem dados artificiais além do teste de fluxo) |
| Skills (produção) | **11 publicadas** (git-versionadas) — Laravel Clean Architecture, Alpine+Livewire, Docker Laravel, PHP Code Quality, Artisan CLI, Controller-Validation-Service, Livewire+Alpine Integration, Docker Environment Setup, Jobs Resilience, PHP Modern Standards, Data Handling |
| Skill groups | 11 (compilados) |
| Testes | **259 verdes** (700 asserções, 1 skip PostgreSQL) — confirmado em 2026-08-11 |

## Decisões pendentes

- **Avaliar adoção formal de `nando/ai-motor`** — fora de escopo agora. Avaliação em [`docs/studies/avaliacao-nando-ai-motor.md`](studies/avaliacao-nando-ai-motor.md). WIP anterior documentado em `WIP-nando-ai-motor.md` foi removido (2026-08-11) porque `composer.json` está limpo e o pacote não deve entrar na `main` sem decisão explícita.

## Próximos passos (curto prazo)

1. **Validar na UI** — 88 memórias `pending` em prod (`/memories`); 6 grupos + 6 skills já aprovados/publicados.
2. **Provisionamento agnóstico de harness (Sprint 4)** — script `curl|bash` idempotente e suporte aos harnesses Codex, Hermes e Antigravity (isolado/adiável).
3. **Investigar qualidade do motor MiniMax** — curadoria em chinês em dev (idioma/prompt).
4. **Criar o scavador** — ferramenta de escavação automatizada (processo hoje manual/script).
5. **🔒 BACKLOG DE SEGURANÇA** — (a) rotacionar token global admin de prod e demais tokens/keys vazados em chat (MINIMAX, postgres, MCP); (b) mover credenciais de prod para env vars via wrapper de inicialização dos harnesses (nunca hardcoded em config); (c) reemitir tokens project-bound com escopo mínimo para integrações MCP; (d) auditar e remover qualquer credencial real remanescente em `~/.config/opencode/opencode.json`, `~/.zshrc`, `.mcp.json`, etc. (substituir por placeholders + env vars). Regra operacional registrada: nunca exibir credenciais reais em chat.

## Concluído (Sprint 1 — 2026-08-11)

- ✅ **Fronteira MCP em dev** — migration `project_id`/`is_global` já em produção; `McpProjectIsolationTest` cobre isolamento total (token project-bound só enxerga próprio projeto + globais; `project` arg é proveniência; legacy falha fechado).
- ✅ **Extração governada de relações por IA** — tool MCP `relation_extract_propose` (chama AnthropicCurationEngine, cria `KnowledgeEdge` com `status=proposed`); UI admin `/admin/relation-proposals` para aprovar/rejeitar; arestas `proposed` NÃO aparecem em `memory_related` (só `validated`).
- ✅ **Testes de regressão** — `tests/Unit/RelationProposerTest.php` (6 testes); `tests/Unit/BootstrapGuardTest.php` (7 testes).

## Concluído (Sprint 2 — 2026-08-12)

- ✅ **Fronteira MCP aplicada em dev local** — migration batch 3; inventário: 0 legados sem `project_id` em captures/tokens/memórias/harness.
- ✅ **Tier 4 ingerido via MCP remoto** — 7 peças do inventário de escavação (#52–58) via `POST /api/mcp` (token project-bound dev-local, porta 9587); pipeline de curadoria MiniMax: 5 memórias curadas + 2 descartadas + 0 failed; 5 validadas manualmente.
- ⚠️ **Divergência de escopo** — Gate 2 previa 48→55 memórias; banco dev local não tem snapshot de prod (vazio por política). Fluxo ponta a ponta validado com as 5 peças do Tier 4.
- ⚠️ **Qualidade do motor** — MiniMax devolveu curadoria em chinês (scraping gov); corrigido manualmente; a investigar (idioma/prompt do engine).
- ✅ **Pipeline de skills em dev** — `group-skills` propôs 5 STANDALONE → 0 grupos (nicho, correto); compile/publish adiados para prod (onde há 48 memórias).

## Concluído (Sprint 3.1 + 3.2 — 2026-08-13, deploy em produção)

- ✅ **Pré-deploy** — inventário em prod: 51 memórias + 52 captures legadas sem `project_id`, 0 projetos, 1 token `is_global` (`Projeto-Eventos-Control`); `.env` compartilhado completo (MINIMAX_API_KEY, DB_QUEUE_RETRY_AFTER, EMBEDDING_*); **backup `pg_dump`** (`devmemory-pre-mcp-20260812-1617.dump`, 145 TOC, validado).
- ✅ **Deploy** — merge `dev`→`main` (`75cab7e`), push com confirmação humana; Jarvis Forge aplicou migration `2026_08_02_create_projects_and_bind_mcp_context` (batch 6); site HTTP 200; sem erros novos no log.
- ✅ **Gate 3.3 — associação administrativa em prod** — 11 `projects` criados (um por `source_project` real: global-standards, events, nandolz, etc.); 51 memórias + 52 captures associadas (0 legadas); token `Projeto-Eventos-Control` (is_global, admin) preservado como operador — enxerga 51/51 via `McpAccessPolicy`; backup `devmemory-pre-gate33-20260813-0631.dump`.
- ✅ **Gate 3.4 — ingestão remota + skills em prod** — 75 novas memórias da escavação ingeridas via `POST /api/mcp` (token global, `source_project` preservado, associadas por mapeamento a 18 projetos); 110 memórias totais; curadoria 0 failed (1 descartada por confiança); pipeline de skills: 6 grupos novos → 6 skills compiladas → **6 publicadas** (total 11 skills, 11 grupos); site HTTP 200, sem erros.

## Concluído (Ondas 1+4+5+6 — 2026-08-22, plano `docs/plans/ondas-2026-08-22.md`)

- ✅ **Onda 1 (Operação DSH)** — token global `dsh-global-new` emitido via DB, `~/.dsh/.env` atualizado, DSH reiniciado, `memory_stats` pulou de 12 → **145** (17 projetos, 9 globais). Higiene do repo: `cookies.txt` removido, commitado em `75ae68a`. **HSTS ativo** no Cloudflare (`max-age=31536000; includeSubDomains; preload`).
- ✅ **Onda 2 (Prova do ciclo de harness)** — perfil `claude-code` v1.0.0 capturado (`files_stored: 3`, `files_with_redactions: 0`, sem segredos). Provision retorna 3 passos `write_file` ordenados (CLAUDE.md → settings.json → .mcp.json) com `had_secrets: false`. Instalador bash validado em `/tmp/harness-test` (HOME fake): (a) execução 1 cria os 3 arquivos exit 0; (b) execução 2 sem `--force` aborta com exit 2 ("Arquivo existe e stdin não é TTY") sem alterar arquivos; (c) execução 3 com `--force` sobrescreve; (d) `FORCE_OVERWRITE=1` equivalente a `--force`.
- ✅ **Onda 3 (Captura contínua via hooks)** — projeto `dev-memory-laravel` criado; token project-bound `hook-dev-memory-laravel` emitido (expira em 90 dias); hook `post_tool_use.sh` instalado em `~/.claude/hooks/`, registrado no `settings.json` em `hooks.PostToolUse`. Token persistido em `~/.claude/.dev-memory-token` (0600). **Smoke test**: (a) evento real → capture `sanitized` → curadoria MiniMax → `curated` (1ª vez) ou `discarded` por baixa confiança (texto genérico); (b) mesmo evento 2× → `deduplicated: true` com **mesmo capture_id** (`01a02cb4-313b-71ac-b91d-b9238d9dabc9`). **Recurrence**: 3 paráfrases enviadas — 2 linkaram à memória existente via `RecurrenceScorer` top-K (text scores 0.55 e 0.70, total 0.56 e 0.66) com `independent=false` (mesmo dia, sem commit); 1 ficou fora do top-K e criou memória nova (trade-off documentado da Onda 4). `recurrence_count` não subiu porque `isIndependent` bloqueia no mesmo dia sem commit — comportamento correto e documentado; para increment real seria preciso `metadata.commit` distinto ou datas diferentes.
- ✅ **Onda 7 (Spike `laravel/mcp` + ADR)** — branch `spike/laravel-mcp` criada com ADR 0002 (`docs/adr/0002-transporte-mcp-laravel-mcp.md`) — análise comparativa entre o servidor MCP manual atual (1.019 LOC) e o pacote oficial `laravel/mcp`. Mapa de equivalência das 20 tools (18 triviais, 2 com design adicional: `memory_delete` 2-phase + `relation_extract_propose` knowledge graph). Decisão proposta: **MIGRAR na Onda 9** (pós-rotação de segredos), após validar 7 critérios de aceite (auth bridge, ConfirmationGuard, knowledge graph, performance, testes, headers, cliente externo). Spike **não entra na main** — fica como research artifact na branch isolada.
- 🟡 **Onda 8 (Rotação de segredos — parcial)** — **Categoria 1 (tokens do hub) concluída**: 2 tokens órfãos revogados via `expires_at = NOW() - INTERVAL '1 hour'` (preserva histórico de auditoria, sem DELETE): `Projeto-Eventos-Control` (12 dias sem uso, plaintext pode ter vazado em chat) e `escavacao-2026-08-13` (nunca usado). Middleware `AuthenticateMcpToken` valida `isExpired()` e rejeita. Mantidos: `dsh-global-new` (DSH), `escavacao-global-2026-08-13` (script de escavação ativo), `DeepSeekHarness`, `Hermes Agent`, `hook-dev-memory-laravel`. Pendente: **Categoria 2** (3 chaves de IA em `~/.dsh/.credentials.yaml` plaintext — reemitir nos painéis), **Categoria 3** (DB/Redis/Mail passwords — rotacionar nos serviços), **Categoria 4** (`APP_KEY` — decisão arquitetural, rotacionar custa re-criptografar `raw_content`/`sanitized_content`).
- ✅ **Onda 4 (Escala de dedup)** — `RecurrenceScorer` indexado via `MemorySearchService` (top-K=30), troca full-scan O(n) por candidatura lexical+semântica+RRF. Floors `TEXT_FLOOR=0.55`/`TOTAL_FLOOR=0.50` mantidos. Deploy em `c705984` via rebase+ff em `main`. 262 testes (3 novos: outside top-K, score máximo, delegação com filtro).
- ✅ **Onda 5 (Busca híbrida na UI)** — `MemoryList::render` delega ao `MemorySearchService` quando `$search` não vazio; badge `search_mode` (hybrid/semantic/lexical/filter) no header da view. Filtros type/scope/stack vão para o serviço; status/doc/maturity aplicados post-search. Paridade UI↔MCP verificada por teste. Deploy em `3ec1c00`. 266 testes (4 novos).
- ✅ **Onda 6 (Operação VPS)** — scheduler `php artisan schedule:run` registrado em `/etc/cron.d/laravel-devmemory` (não estava instalado — auto-cura da fila não rodava). 2 workers `queue:work` confirmados via `supervisorctl status`. Script de visibilidade `devmemory-health-check.sh` em `/usr/local/bin/` + cron a cada 15min em `/etc/cron.d/devmemory-health-check` (alerta se `captures.failed > 0` ou workers < 2). Baseline atual: `captures_failed=0 workers=2 scheduler=1`.

## Notas operacionais

- **Rebuildar assets após mudanças de CSS/Blade** (`npm run build` ou `npm run dev`/`composer dev`). Servir build velho quebra layout/scroll.
- Motor de curadoria: `MINIMAX_API_KEY` no `.env`. Validação documental: `CONTEXT7_API_KEY` (opcional).
- Catálogo de tools MCP: [`docs/mcp-tools.md`](mcp-tools.md).
- Decisão de escopo MCP: [`docs/adr/0001-fronteira-de-projeto-mcp.md`](adr/0001-fronteira-de-projeto-mcp.md).
- Arquitetura semântica e do grafo: [`docs/architecture/memoria-semantica-e-knowledge-graph.md`](architecture/memoria-semantica-e-knowledge-graph.md).
- Deploy em produção (VPS, hook, gotchas): [`docs/deploy.md`](deploy.md).
- **Barreira de testes** — `bin/dev test` roda SQLite `:memory:` (default, isolado); `phpunit.pgsql.xml` usa `dev_memory_test` (CI + paridade). `tests/bootstrap.php` faz fail-closed se env conflitar; `force="true"` no phpunit.xml é primário. **`make migrate-fresh` removido** (incidente 2026-08-02); para resetar schema em dev, use `bin/dev artisan migrate:fresh --seed` explicitamente, confirmando o alvo.
