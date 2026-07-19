# Plano-mestre — Dev Memory Hub (handoff 2026-07-19)

> **Para a próxima sessão (Opus): comece por aqui.** Este plano é autossuficiente — estado
> verificado, fases priorizadas com critérios de aceite, e os gotchas que já custaram caro.
> Fontes de verdade: [`STATUS.md`](../STATUS.md) · [`deploy.md`](../deploy.md) ·
> [`reports/auditoria-2026-07-18.md`](../reports/auditoria-2026-07-18.md) · [`roadmap.md`](../roadmap.md).

## Estado atual (verificado em 2026-07-19)

| | |
|---|---|
| Produção | `https://devmemory.fssdev.com.br` — VPS srv084270, PostgreSQL, SSL, 2 workers de fila |
| Deploy | **Auto-deploy por push na `main`** (webhook Jarvis Forge, release em ~20s, hook roda composer+npm build+migrate+optimize+queue:restart) |
| Testes | 163 verdes (385 asserções) · CI verde |
| Dados prod | 19 memórias (Tier 1 da escavação, todas com prova Context7) · 19 captures · **0 skills / 0 groups** (pipeline de skills nunca rodou em prod) |
| Dados local | 42 memórias (seed + validação local) — SQLite dev |
| Entregas recentes | Tela `/admin/settings` (providers, chaves criptografadas write-only, teste de conexão, queue:restart auto) · 15 fixes da auditoria · responsividade mobile (drawer) · prova Context7 na UI |
| Auditoria | 26 achados confirmados adversarialmente → **15 corrigidos**, **11 pendentes** + 12 lows (ver relatório) |

### Convenções desta base (não quebrar)
- Commits: padrão DEVORQ — pt-BR, `tipo (escopo): descrição`, sem emoji, sem Co-Authored-By.
- Estilo visual **neo-brutalista é intocável** — refinamento é técnico, nunca de estética.
- Todo push na `main` **deploya sozinho** — só empurrar verde (rodar phpunit + pint antes).
- `composer.json` local tem WIP do `nando/ai-motor` (repositório path) — **nunca commitar**.
- Segredos: nunca em código/chat/logs; tela CONFIGURAÇÕES ou `.env`; token MCP de prod está em `/root/.dev-memory-token` na VPS (`ssh vps3`).

### Gotchas que já custaram caro (respeitar)
1. `public/build` é gitignored → `npm run build` obrigatório (o hook faz).
2. Prod é **pgsql:5432** (não mysql) — `shared/.env`.
3. Workers de fila carregam config no start → mudança de config exige `queue:restart` (hook faz; a tela de settings também).
4. `env()` retorna null com config cacheada — sempre `config()`.
5. Context7 funciona **keyless**; MiniMax exige chave (`services.minimax.*`).
6. Painel (`settings` DB) **sobrepõe** env via `SettingsService::applyOverrides()` no boot.

---

> **Progresso:** ✅ FASE 0, ✅ FASE 1, ✅ FASE 2 e ✅ FASE 3 (parcial) concluídas em produção (2026-07-19).
> FASE 3: Tier 2+3 ingeridos (48 memórias, 19 validadas) e **pipeline de skills rodou ponta a
> ponta → 5 skills publicadas** (git). Pendente da FASE 3: Tier 4 (7 peças, opcional), capturar
> configs de harness. As 3 captures FAILED tinham causa real **HTTP 429** (não timeout) — resolvido
> na FASE 2 com backoff; auto-recuperadas pelo agendador horário.
> Próxima recomendada: FASE 3 restante (Tier 4 + harness) ou FASE 4 (inteligência/embeddings).

## FASE 0 — Housekeeping ✅ FEITA

- [ ] **`DB_QUEUE_RETRY_AFTER=330` no `shared/.env` da VPS** (ação manual do usuário; sem isso, job de curadoria >90s é re-reservado por outro worker e marcado como falho enquanto roda):
  `echo "DB_QUEUE_RETRY_AFTER=330" >> /var/www/devmemory.fssdev.com.br/shared/.env && cd /var/www/devmemory.fssdev.com.br/current && php artisan config:clear && php artisan queue:restart`
- [ ] Smoke: `https://devmemory.fssdev.com.br` 200, login ok, `/admin/settings` renderiza.

## FASE 1 — Bloco de segurança ✅ FEITA (commit a7554b3)

> Aceite geral: suíte verde + teste novo por item + nada quebra o fluxo MCP existente.

1. **RBAC mínimo** (achado #7 + flag do security review no `MemoryList::promoteMemory`):
   migration `users.is_admin` (bool, default false) · `memory:make-admin` seta true ·
   Gate `admin` + middleware no grupo `/admin/*` · checagem nas ações mutantes dos
   componentes Livewire (delete/approve/publish/revoke/promote/settings).
   *Aceite:* user não-admin autenticado recebe 403 em `/admin/*` e nas ações; testes cobrem.
2. **Criptografia at-rest do `raw_content`/`sanitized_content` das captures** (achado #5):
   cast `encrypted` + migration de re-gravação dos registros existentes (19 em prod).
   *Cuidado:* payloads grandes (MAX_LENGTH 20k) — medir; `LIKE` em captures não é usado, ok.
3. **Tokens MCP com expiração** (achado #10): `expires_at` nullable + checagem no
   `AuthenticateMcpToken` + campo na UI de emissão (default: sem expiração) + revogar exibe last_used.
4. **Allowlist de paths no harness** (achado #11): validar `path` do `harness_capture` contra
   prefixos seguros (ex.: `~/.claude/`, `.mcp.json`, `CLAUDE.md`) antes de virar passo `write_file`.

## FASE 2 — Robustez operacional ✅ FEITA (commits 11bf5ba + d191fa1)

5. ✅ **Visibilidade de failed_jobs na UI** — banner de capturas FAILED + botão "REPROCESSAR FALHAS" no CapturesInbox.
6. ✅ **Paginação** em SkillsAdmin, SkillGroupsReview, HarnessProfiles (WithPagination + paginate(20)).
7. ✅ **highlight.js: CDN → bundle local** (core + 12 linguagens tree-shaken, atom-one-dark) + re-highlight pós-morph Livewire.
8. ✅ **Labels acessíveis** — `neo/input` gera `id` automático p/ `for=`.
9. ✅ **Agendar recuperação**: `memory:process-captures --retry-failed` a cada hora (`withoutOverlapping`).
10. ✅ **Backoff exponencial em 429/5xx** (commit d191fa1) — o motor retenta só em transiente
    (conexão, 429, 500, 502, 503, 504) com backoff exponencial + Retry-After; 4xx falha na hora.
    Testes com `Sleep::fake()` cobrem 429→200 e 429 persistente.
11. ✅ **`memories.type` varchar(20)→varchar(50)** (commit c74bb90) — 2º bug, escondido atrás do 429:
    a migration 2026_07_10 liberou `architecture_decision` (21 chars) no CHECK mas deixou a coluna
    varchar(20) → insert estourava em Postgres (SQLite não enforça, testes não pegavam). + rede de
    proteção no `CurateCaptureJob` (erro de persistência é registrado e vira FAILED, não derruba o
    worker) + guard `EnumColumnWidthTest` (largura-vs-enum, DB-agnóstico).

> **As 3 capturas FAILED da FASE 3 foram RESOLVIDAS e curadas (2026-07-19):** eram os dois bugs acima
> empilhados. Prod: 48→51 memórias, failed=0. Deploy do Jarvis Forge roda migrations automaticamente.

## FASE 3 — Produto: o hub trabalhando de verdade

10. **Ingerir Tier 2–4 em produção** (~77 peças restantes do inventário
    `docs/studies/escavacao-projetos-2026-07-16.md`): mesmo processo do Tier 1 — payloads JSON-RPC
    `memory_ingest` via `https://devmemory.fssdev.com.br/api/mcp` (token na VPS), fila processa.
    **Coordenar com o usuário o que promover antes (Fase 3 da skill excavate-knowledge).**
11. **Validar/promover memórias** na UI com a prova Context7 (19+ pendentes).
12. **Rodar pipeline de skills em prod** (`memory:group-skills` → aprovar grupos na UI →
    `memory:compile-skills` → aprovar → `memory:publish-skills`). Prod tem 0 skills hoje.
13. **Capturar configs de harness** da escavação (25 CLAUDE.md / 30 .mcp.json) via `harness_capture`.

## FASE 4 — Inteligência (roadmap de produto)

14. **Embeddings/pgvector** — substitui TF-cosseno do RecurrenceScorer (que carrega TODAS as
    memórias em RAM por captura — achado #14; escala mal >centenas). Verificar `CREATE EXTENSION
    vector` na VPS; decidir provedor de embeddings (decisão pendente do usuário); coluna vector +
    busca semântica no memory_search.
15. **Campo `maturity`** (workaround → provisório → recomendado → canônico → consolidado) —
    enum + migration + UI + filtros + promoção influenciada pela recorrência.
16. **Captura contínua** — hooks do harness (PostToolUse/Stop) alimentando `memory_ingest`
    automaticamente (o hub deixa de depender de escavação manual).

## FASE 5 — Harness provisioning (roadmap, fases 3–5 do doc)

17. Instalador `curl|bash` idempotente por harness.
18. Adapters Codex/Hermes/Antigravity (`HarnessType` + `recommendedPaths`).
19. Confirmação de sobrescrita no provision (padrão ConfirmationGuard).

---

## Ordem recomendada e porquê

**0 → 1 → 3 → 2 → 4 → 5.** Segurança primeiro (Fase 1: são os buracos confirmados com maior
consequência; RBAC destrava multiusuário com segurança). Depois **Fase 3** — é o *valor* do
produto (o hub existe para isso; hoje só 19 de ~95 peças estão dentro e não há skill publicada
em prod). Fase 2 intercala bem como itens avulsos entre blocos. Fases 4–5 são evolução.

## Backlog menor (oportunista, sem fase)
- Senha temporária + troca forçada no 1º acesso (onboarding multiusuário — pós-RBAC).
- Otimização de gatilho das skills locais (loop skill-creator).
- Lows restantes do relatório da auditoria (cookie Secure já documentado no runbook, etc.).
- `memory:curate` piloto hardcoda engine (achado #26, low).

## Como retomar (checklist da 1ª mensagem da sessão futura)
1. Ler este plano + `docs/reports/auditoria-2026-07-18.md`.
2. `git status` (esperado: só `composer.json` WIP) · `git log --oneline -5` · CI verde?
3. Confirmar FASE 0 feita (retry_after na VPS).
4. Perguntar ao usuário qual fase atacar (recomendação: Fase 1 completa num bloco).
5. Antes de cada push: `./vendor/bin/pint --dirty && ./vendor/bin/phpunit` — push = deploy.
