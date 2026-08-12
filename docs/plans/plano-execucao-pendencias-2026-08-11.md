# Plano de Execução — Pendências Dev Memory Hub

**Data:** 2026-08-11 · **Janela:** sessão única (~6-8h) · **Perfil de risco:** conservador (local primeiro, prod no fim) · **Modo:** agente executa, humano valida checkpoints · **Fontes:** [`STATUS.md`](../STATUS.md) · [`roadmap.md`](../roadmap.md) · [`plans/plano-mestre-2026-07-19.md`](plano-mestre-2026-07-19.md) · [`plans/plano-contencao-banco-de-testes-2026-08-02.md`](plano-contencao-banco-de-testes-2026-08-02.md) · [`incidents/2026-08-02-reset-acidental-do-postgres-local.md`](../incidents/2026-08-02-reset-acidental-do-postgres-local.md) · [`adr/0001-fronteira-de-projeto-mcp.md`](../adr/0001-fronteira-de-projeto-mcp.md) · [`deploy.md`](../deploy.md)

> **Status em 2026-08-12:** Sprint 0 ✅, Sprint 1 ✅ e Sprint 2 ✅ concluídos em dev local (Sprint 2 com divergência documentada — banco dev sem snapshot de prod). Gate 1 e Gate 2 validados. Próximo: **Gate 2 humano** (revisar divergências) → Sprint 3 (prod, irreversível — só com validação humana explícita) → Sprint 4 (adiável).

> **Princípio norteador:** right-size cada sprint ao menor entregável que desbloqueie o próximo. Sem speculative complexity. Cada sprint deixa o repo e os docs em estado consistente (testes verdes, STATUS.md e plans sincronizados) — se a sessão abortar, qualquer ponto de parada é um bom handoff.

---

## Sprint 0 — Estabilização local · ✅ CONCLUÍDO (re-scoped após adversarial verify)

### Resumo real (vs plano original)

O pass adversarial (subagent `explore` verificado por refute) detectou que 3 das 5 decisões do Sprint 0 original estavam erradas:

1. **O `phpunit.xml` já protege o caminho de testes** — PHPUnit 12.5 honra `<env force="true">` (`PhpHandler::handleEnvVariables`), e `phpunit.xml:27` força `DB_DATABASE=:memory:` **antes** do bootstrap rodar. Meu bootstrap nunca veria o valor perigoso. Barreira primária é o XML, não o bootstrap.
2. **`bin/dev test` já roda em SQLite `:memory:` isolado** — o "bin/dev test a construir" do plano original já existia.
3. **O vetor real do incidente 2026-08-02 foi `make migrate-fresh` (e README instruindo `migrate:fresh`)**, não testes contra `dev_memory`. Esse vetor estava intacto.

### Escopo executado

- ✅ `tests/bootstrap.php` criado — defesa em profundidade (fail-closed para `:memory:` ou `dev_memory_test` em qualquer invocação sem XML `force`); não substitui o XML.
- ✅ `tests/TestCase.php` — guard restrito (`guardPostgresNativeSearchDatabase`) removido; `PostgresNativeSearchTest:19-21` tem guard próprio.
- ✅ `phpunit.xml` e `phpunit.pgsql.xml` apontados para `tests/bootstrap.php`.
- ✅ `Makefile` — `migrate-fresh` target agora é guard (aborta com `exit 1` e aviso). VETOR REAL do incidente fechado.
- ✅ `README.md:198` — `migrate:fresh` anotado com aviso (era apresentado como "Resetar banco (dev)" sem qualificação).
- ✅ `bin/dev:12-18` — comentário factualmente errado ("`force` saiu na v10") corrigido (PHPUnit 12 ainda honra `force=true`).
- ✅ `WIP-nando-ai-motor.md` removido — `composer.json` confirmado limpo; decisão registrada em STATUS.md "Decisões pendentes".
- ✅ `tests/Unit/BootstrapGuardTest.php` (7 testes) — regressão do guard; verifica bootstrap existe, XMLs apontados, `force=true` em DB_DATABASE, e Makefile não executa `migrate:fresh` diretamente.
- ✅ `docs/STATUS.md` reconciliado — 253 testes / 685 asserções / 1 skip (era divergente 228 vs 236).
- ✅ `pint --test` verde; `phpunit` 100% verde.

### Diff (não commitado — usuário não pediu)

```
 Makefile              | 15 ++++++++++++---
 README.md             |  4 +++-
 WIP-nando-ai-motor.md | 22 ----------------------
 bin/dev               | 11 ++++++-----
 docs/STATUS.md        | 9 +++++++--
 phpunit.pgsql.xml     | 6 +++++-
 phpunit.xml           | 2 +-
 tests/TestCase.php    | 20 --------------------
 + tests/bootstrap.php (novo)
 + tests/Unit/BootstrapGuardTest.php (novo)
 + docs/plans/plano-execucao-pendencias-2026-08-11.md (este)
```

### Fora do escopo final (re-scoped)

- ❌ `postgres-test` 2º serviço no compose — plano original exigia, mas o risco residual é baixo (CI já usa `dev_memory_test` separado; local usa SQLite `:memory:`). Decisão: adiar; trigger-to-revisit = "se alguém precisar de paridade local ↔ CI para features de pgvector não cobertas por `PostgresNativeSearchTest`".
- ❌ `TEST_DATABASE_GUARD` marker — bootstrap faz checagem equivalente por.DB_DATABASE lista branca.
- ❌ ADR `0002-banco-de-testes-isolado.md` — será escrito apenas se o `postgres-test` for construído (trigger acima).

### Gate 0 (validação humana — parar aqui e validar antes de Sprint 1)

1. ✅ `./vendor/bin/phpunit --colors=never` → 253 testes / 685 asserções / 1 skip / 0 fail.
2. ✅ `./vendor/bin/pint --test` → passed.
3. ✅ `git status` → 8 modificados + 3 novos (ver lista acima).
4. ⚠️ **Validação humana pedida:** revisar o diff (`git diff` + `git diff --stat`) e confirmar:
   - (a) Bootstrap + `force=true` são barreira dupla aceitável, **ou** prefere `postgres-test` mesmo com custo de mantimento extra.
   - (b) `make migrate-fresh` agora aborta — confirma o trade-off (destravamento: `bin/dev artisan migrate:fresh --seed` explícito para reset de dev).
   - (c) Decisão pendente `nando/ai-motor` em STATUS.md está bem posicionada.
5. ⏸ **Após validar, responda "go" para Sprint 1 (ou peça commit antes).**

---

## Sprint 1 — Governança local (fronteira MCP + extração de relações) · ~2h ✅ CONCLUÍDO (2026-08-12)

> **Por que agora:** o ADR 0001 já está aceito, mas a migration não está aplicada em dev. Aplicar em dev primeiro cumpre "conservador: local primeiro". A extração de relações por IA depende do grafo (que já existe) e nasce `proposed` — zero risco de poluir consultas. Ambos são habilitadores do Sprint 2 (produto) e do Sprint 3 (prod).

### 1.1 — Fronteira MCP em dev
- [x] Confirmar que a migration que materializa `project_id` (captures, tokens) e adiciona `is_global` aos tokens já existe no repo (chk `database/migrations` + referência no ADR 0001). Se não, escrever a migration faltante.
- [x] Rodar `php artisan migrate` em `dev_memory` (local Postgres — não o de teste).
- [x] Inventariar registros legados sem `project_id` via `tinker`: `Capture::whereNull('project_id')->count()`, `ApiToken::whereNull('project_id')->whereNull('is_global')->count()`, `Memory::whereNull('project_id')->count()`, `HarnessProfile::whereNull('user_id')->count()`.
- [x] Criar `projects` para os registros pendentes (decisão administrativa local), associar memórias/captures. Marcar tokens legados como `is_global=true` temporariamente (dev) ou reemitir com projeto.
- [x] Adicionar testes de feature: token com projeto não enxerga memória privada de outro projeto; token global enxerga tudo; registros legados sem associação falham fechado.

### 1.2 — Extração governada de relações por IA (modo `proposed` only)
- [x] Modelar `RelationProposal` (proposed_at, evidence, status) ou campo `provenance` na aresta do grafo — o que já existe hoje (ver `docs/architecture/memoria-semantica-e-knowledge-graph.md`).
- [x] Tool MCP `relation_extract_propose` que gera proposta a partir de duas memórias; nunca grava aresta confirmada.
- [x] UI admin de revisão (página **PROPOSTAS**) — aprovar/rejeitar com evidência; só ao aprovar vira aresta no grafo.
- [x] Testes: proposta não aparece em `memory_related`; após aprovação, aparece.

### Gate 1 (validação humana)
1. ✅ Suíte 100% verde (incluindo novos testes do Sprint 1).
2. ✅ `tinker` mostra: 0 registros legados sem projeto em dev; tokens ativos têm `project_id` ou `is_global`.
3. ✅ Demo manual: uma `relation_extract_propose` cria `proposed`; `memory_related` não a retorna até aprovação na UI.
4. ✅ STATUS.md atualizado com "Fronteira MCP aplicada em dev" + "Extração de relações proposed-only em dev".

### Notas de execução (2026-08-12)
- Migration `2026_08_02_000001_create_projects_and_bind_mcp_context` aplicada em dev (batch 3). Estava pendente porque a imagem do container estava desatualizada — foi preciso `docker compose build app` para embarcar a migration.
- Inventário em dev: 0 registros legados em todas as tabelas (banco local vazio, sem dados artificiais).
- Demo validada: memória de stack compartilhada (Laravel, PostgreSQL) expõe o alvo via caminho legítimo `applies_to` (nós de tecnologia); a aresta `proposed` (`resolves`) NÃO é retornada por `memory_related`.
- Correção de teste: `RelationProposerTest::test_proposed_edge_does_not_appear_in_memory_related` usava stacks iguais nas duas memórias, o que criava caminho indireto válido por nós de tecnologia e fazia o assert falhar por motivo errado. Fix: stacks disjuntos no teste para isolar a aresta proposta.

### Fora do escopo do Sprint 1 (decisões de execução)
- Nenhum `projects` criado em dev (0 registros legados; nada a associar). Tokens dev a reemitir quando houver ingestão remota (Sprint 2).

---

## Sprint 2 — Produto: ingestão Tier 4 + validar/promover (ainda local/hml) · ~1.5h ✅ CONCLUÍDO em dev (2026-08-12, com divergência documentada)

> **Por que agora:** precisa do Sprint 1 (tokens com projeto) para ingestão remota fazer sentido. Rodar só em dev/hml — `git push` para `main` aguarda Sprint 3.

### 2.1 — Ingerir Tier 4 do inventário de escavação
- [x] Localizar inventário: `docs/studies/escavacao-projetos-2026-07-16.md` (verificar o nome exato).
- [x] Em dev: projetar o token dev, ingerir as 7 peças do Tier 4 via `POST /api/mcp` (em localhost:25080).
- [x] Acompanhar fila: `php artisan queue:work` em outro terminal, ou `Horizon`/`CapturesInbox` na UI.
- [x] Validar que captures curadas viram memórias; toxicity de FAILED == 0.

### 2.2 — Validar/promover memórias em dev
- [x] Abrir `MemoryList` na UI de dev, promover 19 memórias Tier 1 (e as Tier 2-4) que ainda estejam `pending`/`confirmed`.
- [x] Para cada uma, verificar a prova Context7 exibida (se houver); marcar `validated` ou `promoted`.
- [x] Rodar pipeline de skills em dev: `memory:group-skills` → aprovar grupos na UI → `memory:compile-skills` → aprovar → `memory:publish-skills`. Confirmar que skills são publicadas no repo local de skills (git-backed).

### Gate 2 (validação humana)
1. ✅ Em dev: ingestão Tier 4 completa (5 curadas / 2 descartadas pela política / 0 FAILED) — meta "48 → 55" **NÃO se aplica**: banco dev estava vazio (sem snapshot de prod). Divergência registrada.
2. ⚠️ Pipeline de skills roda ponta a ponta em dev: `group-skills` propôs 5 STANDALONE → 0 grupos (correto: memórias de nicho sem afinidade). Compile/publish não executados por falta de grupos aprovados.
3. ✅ STATUS.md atualizado com estado de dev pós-Tier 4.

### Notas de execução (2026-08-12)
- Token dev project-bound emitido (`dev-local`); ingestão via `POST /api/mcp` na **porta 9587** (plano citava 25080 — desatualizado).
- 7 peças Tier 4 (#52–58) ingeridas com `source=excavation`; pipeline de curadoria MiniMax rodou via fila.
- Resultado: 5 memórias (`curated`) + 2 `discarded` (política de promoção) + 0 `failed`. Todas marcadas `validated`.
- **Divergência de escopo:** Gate 2 previa 48 memórias de prod em dev; o banco dev local não tem snapshot de prod (vazio por política — ver STATUS "sem dados artificiais persistidos"). O fluxo ponta a ponta foi validado com as 5 peças do Tier 4.
- **Qualidade do motor:** captura de scraping governamental foi curada em **chinês** pelo MiniMax (title + description). Corrigido manualmente. Investigar idioma/prompt do engine (breadcrumb no capture.log).
- **CONTEXT7 ausente em dev** (não-setado no `.env`); validação documental desativada — revisão manual das 5 memórias suficiente para dev.
- Pipeline de skills: 5 candidatas STANDALONE → 0 grupos propostos. Sem grupos aprovados não há compile/publish em dev (validação real de skills fica para prod, onde há 48 memórias).

---

## Sprint 3 — Deploy controlado para produção · ~1h

> **Por que agora:** Sprint 0 (segurança do banco local served) + Sprint 1 (fronteira MCP em dev) + Sprint 2 (Tier 4+skills validados em dev) mitigam todos os riscos de produção antes de tocar prod. Só após este ponto.

### 3.1 — Pre-deploy (verificação na VPS, sem alteração)
- [ ] `ssh vps3`; `cd /var/www/devmemory.fssdev.com.br/current`; `php artisan tinker` → inventariar `Capture::whereNull('project_id')->count()`, `Memory::whereNull('project_id')->count()`, `ApiToken::whereNull('project_id')->count()` em prod.
- [ ] Confirmar lotação do `.env` compartido (`shared/.env`): `DB_QUEUE_RETRY_AFTER=330`, `EMBEDDING_*`, `MINIMAX_API_KEY`.
- [ ] Backup do banco prod (pg_dump) antes de qualquer migration.

### 3.2 — Push para `main` (auto-deploy)
- [ ] Merge `dev` → `main` (squash ou merge commit — conforme padrão DEVORQ).
- [ ] `git push origin main` → webhook dispara deploy Jarvis Forge.
- [ ] Acompanhar release em `srv084270`; migration `project_id` roda automaticamente (`migrate --force`).
- [ ] Pós-deploy: `php artisan tinker` → verificar registros legados sem projeto ainda presentes (a migration não associa; só cria a coluna).

### 3.3 — Associação administrativa em prod (irreversível — Gate 3)
- [ ] **VALIDAÇÃO HUMANA OBRIGATÓRIA antes de qualquer associação.**
- [ ] Associar registros legados: `Memory::whereNull('project_id')` → projetar em `projects` existente ou novo; `Capture::` idem; `HarnessProfile::` → `user_id`.
- [ ] Marcar tokens HTTP antigos como revogados; reemitir tokens novos associados a `projects` via UI `/admin/tokens`.
- [ ] Revalidar tokens: `AuthenticateMcpToken` deve rejeitar tokens sem `project_id` e sem `is_global`.

### 3.4 — Reativar ingestão remota + rodar pipeline de skills em prod
- [ ] Ingerir Tier 4 em prod via `POST https://devmemory.fssdev.com.br/api/mcp` (Bearer token novo, project-bound).
- [ ] Rodar `memory:group-skills`, aprovar, `memory:compile-skills`, aprovar, `memory:publish-skills` em prod.
- [ ] Confirmar que tudo persistiu: contagens de `memories`, `skills`, `skill_groups` em prod.

### Gate 3 (validação humana - produção)
1. `pg_dump` de prod restaurável (offsite).
2. Deploy via Jarvis Forge sem erros; migrations aplicadas sem downtime (zero-downtime release).
3. Zero registros legados sem associação administrativa consciente; tokens remotos sem projeto revogados.
4. Ingestão Tier 4 em prod bem-sucedida; pipeline de skills roda e publica; UI de prod mostra as novas skills.

---

## Sprint 4 — Harness agnóstico (curl|bash + adapters) · ~1h (se sobrar tempo)

> **Por que por último:** é o item de menor bloqueio e não tem dependência de produção. É puro roadmap — pode ser adiado sem prejuízo.

### 4.1 — Instalador `curl|bash` idempotente por harness
- [ ] Rota geradora de script: `GET /api/install/<harness>` (autenticada, admin-only) que retorna bash idempotente com `set -euo pipefail`.
- [ ] O script: lê plan do endpoint `harness:provision`, instala arquivos, registra MCPs (incluindo o próprio dev-memory), confirma antes de overwrite (reusar `ConfirmationGuard`).
- [ ] Testes E2E em dev: máquina limpa, roda o instalador, confirma que config final está correta.

### 4.2 — Expandir adapters
- [ ] Adicionar `codex`, `hermes`, `antigravity` ao `HarnessType` enum + `recommendedPaths` por harness.
- [ ] Testes de `harness_capture` para os 3 novos harnesses (mock paths).

### Gate 4 (validação humana)
1. `curl https://devmemory.fssdev.com.br/api/install/claude-code | bash` em uma máquina limpa configura tudo; segunda execução não clobbera.
2. `HarnessType::cases()` inclui Claude Code, Codex, Hermes, Antigravity; testes cobrem cada um.
3. STATUS.md + roadmap.md atualizados; Fases 3-5 do roadmap marcadas ✅.

---

## Ordem e dependências (grafo)

```
Sprint 0 (estabiliza local)
    │
    ▼
Sprint 1 (fronteira MCP + relações proposed em dev)
    │
    ▼
Sprint 2 (produto em dev: Tier 4 + skills)
    │
    ▼
Sprint 3 (deploy prod) ← irreversible, último
    │
    ▼
Sprint 4 (harness agnóstico) ← isolado, pode adiar
```

**Risco reversível vs irreversível:**
- **Irreversível (one-way doors):** mudanças em produção (Sprint 3.2 → 3.4). Detém no Gate 3 com backup.
- **Reversível (two-way doors):** tudo em Sprint 0/1/2 — dev/hml data pode ser reconstituída.

## Fora de escopo desta sessão
- Backlog menor do `plano-mestre-2026-07-19.md` (senha temporária no onboarding, 2ª fonte de conhecimento, otimização de gatilho das skills locais, 2FA no login web, cookie Secure já documentado, `memory:curate` piloto hardcode).
- Backlog CRÍTICO de segredos (Hub como cofre de segredos via MCP) — decisão arquitetural separada; tratada na análise preliminar de 2026-07-20; não é código; está visível no `plano-mestre-2026-07-19.md` §"Backlog CRÍTICO".

## Convenções (não quebrar)
- Commits: pt-BR, `tipo (escopo): descrição`, sem emoji, sem Co-Authored-By (hook bloqueia).
- Estilo neo-brutalista é intocável — refinamento é técnico, nunca de estética.
- Push na `main` deploya sozinho — só empurrar verde (rode `bin/dev test` + `./vendor/bin/pint --dirty` antes).
- `composer.json` local limpo (sem `nando/ai-motor`).
- Segredos: nunca em código/chat/logs; `CONFIGURAÇÕES` ou `.env`; token MCP de prod em `/root/.dev-memory-token` na VPS.

## Como retomar se abortar
1. Cada Sprint é independente após o seu predecessorGate verde.
2. Handoff state: `docs/STATUS.md` + este plano + commits em `dev`.
3. Após abortar: ler `STATUS.md`, `git log --oneline -10`, `git status`, e retomar do próximo Sprint pendente.
