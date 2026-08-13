# STATUS — Dev Memory Hub

**Atualizado:** 2026-08-13 · **Saúde local:** 260 testes verdes / 701 asserções, 1 skip (gate PostgreSQL local) · **Estado:** Sprints 1–3 concluídos — fronteira MCP aplicada e **dados legados associados a 11 projetos em prod** (Gate 3.3 ✅), deploy saudável; **Gate 3.4 pendente** (ingestão remota + skills em prod)

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
| Memórias em produção | 48 no último snapshot documentado (2026-07-18; não reverificado nesta mudança local) |
| Banco local validado | PostgreSQL 16 + pgvector 0.8.2; migrations aplicadas; fronteira MCP (batch 3); dev com 5 memórias do Tier 4 (sem dados artificiais além do teste de fluxo) |
| Skills (produção) | **5 publicadas** (git-versionadas) — Laravel Clean Architecture, Alpine+Livewire, Docker Laravel, PHP Code Quality, Artisan CLI |
| Skill groups | 5 (compilados) |
| Testes | **259 verdes** (700 asserções, 1 skip PostgreSQL) — confirmado em 2026-08-11 |

## Decisões pendentes

- **Avaliar adoção formal de `nando/ai-motor`** — fora de escopo agora. Avaliação em [`docs/studies/avaliacao-nando-ai-motor.md`](studies/avaliacao-nando-ai-motor.md). WIP anterior documentado em `WIP-nando-ai-motor.md` foi removido (2026-08-11) porque `composer.json` está limpo e o pacote não deve entrar na `main` sem decisão explícita.

## Próximos passos (curto prazo)

1. **Gate 3.4** — reativar ingestão remota em prod: ingerir as 102 memórias da escavação local via MCP project-bound, rodar pipeline de skills (`group → compile → publish`), validar/promover.
2. **Provisionamento agnóstico de harness (Sprint 4)** — script `curl|bash` idempotente e suporte aos harnesses Codex, Hermes e Antigravity (isolado/adiável).
3. **Investigar qualidade do motor MiniMax** — curadoria em chinês em dev (idioma/prompt).
4. **Ingerir Tier 1–3** do inventário de escavação (Tier 4 já validado o fluxo).
5. **Criar o scavador** — ferramenta de escavação automatizada (processo hoje manual/script).

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

## Notas operacionais

- **Rebuildar assets após mudanças de CSS/Blade** (`npm run build` ou `npm run dev`/`composer dev`). Servir build velho quebra layout/scroll.
- Motor de curadoria: `MINIMAX_API_KEY` no `.env`. Validação documental: `CONTEXT7_API_KEY` (opcional).
- Catálogo de tools MCP: [`docs/mcp-tools.md`](mcp-tools.md).
- Decisão de escopo MCP: [`docs/adr/0001-fronteira-de-projeto-mcp.md`](adr/0001-fronteira-de-projeto-mcp.md).
- Arquitetura semântica e do grafo: [`docs/architecture/memoria-semantica-e-knowledge-graph.md`](architecture/memoria-semantica-e-knowledge-graph.md).
- Deploy em produção (VPS, hook, gotchas): [`docs/deploy.md`](deploy.md).
- **Barreira de testes** — `bin/dev test` roda SQLite `:memory:` (default, isolado); `phpunit.pgsql.xml` usa `dev_memory_test` (CI + paridade). `tests/bootstrap.php` faz fail-closed se env conflitar; `force="true"` no phpunit.xml é primário. **`make migrate-fresh` removido** (incidente 2026-08-02); para resetar schema em dev, use `bin/dev artisan migrate:fresh --seed` explicitamente, confirmando o alvo.
