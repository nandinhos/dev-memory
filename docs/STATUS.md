# STATUS — Dev Memory Hub

**Atualizado:** 2026-07-18 · **Saúde:** 153 testes verdes · **Estado:** **EM PRODUÇÃO** — `https://devmemory.fssdev.com.br` (VPS srv084270, PostgreSQL, SSL) · essência + harness (Claude Code) + Tier 1 da escavação ingerido

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
   │  UI Livewire "neo" (auth)      MemoryMcpServer (15 tools)│
   │   ├ Dashboard / Memórias        ├ leitura   (list/search/get/stats)
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
   │  PostgreSQL/SQLite · Redis · repo git de skills        │
   └────────────────────────────────────────────────────────┘

   ✋ = ação destrutiva com confirmação em duas fases
```

## Capacidades (o que funciona)

- ✅ **Busca semântica & pgvector** — busca híbrida com embeddings (Ollama primário custo zero + MiniMax fallback, pgvector em Postgres, fallback JSON em SQLite)
- ✅ **Ciclo de maturidade** — `MemoryMaturity` (workaround → provisional → recommended → canonical → consolidated) com políticas de transição governadas
- ✅ **Autenticação** — login neo, rotas protegidas, `memory:make-admin`
- ✅ **Gestão de memórias** — CRUD, filtros, validação, promoção a global
- ✅ **Pipeline de curadoria** — ingestão imutável, sanitização, curadoria MiniMax (structured output + reparo), política de promoção, recorrência composta
- ✅ **Validação documental** — Context7 (RAG fundamentado), auto-validação só de `confirmed` ≥ 0.8
- ✅ **Skills** — agrupamento por IA, compilação com rastreabilidade de fonte, publicação git versionada
- ✅ **MCP remoto** — 15 tools, HTTP + stdio, tokens de API (emitir/revogar na UI)
- ✅ **Consulta preventiva** — `hub_briefing` antes de implementar
- ✅ **Provisionamento de harness (Claude Code)** — sobe a config do ambiente (sanitizada) e replica em máquina limpa via MCP (`harness_capture/provision`, página **HARNESS**, `harness:capture-local`)
- ✅ **Segurança** — sem credenciais hardcoded, confirmação de ações destrutivas, sem API aberta, segredos redigidos na captura de config

## Dados atuais

| | |
|---|---|
| Memórias (produção/local) | 48 memórias indexadas com vetores de embedding 1536 dimensões |
| Skills (produção) | **5 publicadas** (git-versionadas) — Laravel Clean Architecture, Alpine+Livewire, Docker Laravel, PHP Code Quality, Artisan CLI |
| Skill groups | 5 (compilados) |
| Testes | **207 verdes** (544 asserções) |

## Próximos passos (curto prazo)

1. **Provisionamento agnóstico de harness** — script `curl|bash` idempotente e suporte aos harnesses Codex, Hermes e Antigravity.
2. **Ingerir Tier 4** do inventário de escavação em produção + rodar o pipeline de skills (`group → compile → publish`).
3. **Validar/promover** as memórias de produção (revisão manual com a prova Context7).
3. **Embeddings/pgvector reais** — recorrência/busca hoje usa TF-cosseno; agora que prod está em Postgres, trocar.
4. **Campo `maturity`** (workaround → canônico) para distinguir conhecimento provisório de consolidado.

## Notas operacionais

- **Rebuildar assets após mudanças de CSS/Blade** (`npm run build` ou `npm run dev`/`composer dev`). Servir build velho quebra layout/scroll.
- Motor de curadoria: `MINIMAX_API_KEY` no `.env`. Validação documental: `CONTEXT7_API_KEY` (opcional).
- Catálogo de tools MCP: [`docs/mcp-tools.md`](mcp-tools.md).
- Deploy em produção (VPS, hook, gotchas): [`docs/deploy.md`](deploy.md).
