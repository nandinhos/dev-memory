# Escavação de Conhecimento — `~/projects` (2026-07-16)

> Inventário durável produzido pela skill `excavate-knowledge`. É um **artefato de hand-off**:
> o hub dev-memory (MCP) **não estava conectado** nesta sessão, então a ingestão (Fase 4) fica
> pendente. Quando o hub estiver conectado, use este inventário para promover o conjunto aprovado
> via `memory_ingest` (conhecimento) e `harness_capture` (configs).

## Números honestos

- **Fontes brutas encontradas pelo scanner (alto sinal):** 38 arquivos (23 `lesson` + 7 `troubleshooting` + 8 `convention`).
- **Ruído descartado:** 9 arquivos `lessons.md` (hash `627d6064`, 6 linhas) são **template de slash-command idêntico**, sem conteúdo — descartados.
- **Cópias exatas / near-dups entre projetos:** 4 pares (2 variantes `LICOES-AMBIENTE-DEV`, 2 variantes `LICOES-CLICKUP`, `nandolz`↔`teste/nando-lz`, `events`↔`nando-events-main`). Provenance canônica escolhida abaixo.
- **Fontes de conteúdo único real:** **~18 arquivos.**
- **Peças brutas extraídas pelos agentes leitores:** ~207.
- **Peças distintas após dedup semântico:** **~95** (o arquivo `global-standards` já é a consolidação do próprio usuário; subsume grande parte das lições Laravel/Livewire/PHP dos demais).

## Validação documental (Context7)

Validadas as 3 claims mais reutilizáveis e version-específicas do stack do hub (Laravel 13 / Livewire 4 / PHP 8.4):

| Claim | Veredito | Fonte oficial |
|-------|----------|---------------|
| Livewire 4 injeta/gerencia Alpine automaticamente; não importar Alpine manualmente | ✅ confirmado | livewire.laravel.com/docs/4.x/alpine + /troubleshooting |
| `HasUuids` gera UUID **v7** por padrão no Laravel 12 (usar `HasVersion4Uuids` p/ v4) | ✅ confirmado | laravel.com/docs/12.x/eloquent + /upgrade |
| `ConvertEmptyStringsToNull` converte string vazia → `null` antes do controller | ✅ confirmado | laravel.com/docs/12.x/requests |

As demais claims técnicas ficam **➖ não-validadas (verossímeis)** — validar na promoção. Claims de
domínio/ferramenta pessoal são **➖ não-verificáveis** por natureza.

Coluna **"já no hub?"** = `não verificado` em todo o inventário (MCP `memory_search` offline + hub ainda não deployado na VPS; o DB local é seed/teste).

---

## TIER 1 — Alto sinal, alto reuso, no stack exato do hub (Laravel 13 · Livewire 4 · PHP 8.4 · Tailwind 4)

Candidatos mais fortes à promoção. Muitos aparecem em 2–3 fontes independentes (marcado em "fontes").

| # | tipo | afirmação (núcleo) | fontes | c7 |
|---|------|--------------------|--------|-----|
| 1 | anti_pattern | **Nunca importar Alpine manualmente com Livewire 4** — Livewire injeta/gerencia o Alpine; import duplo dispara "multiple instances of Alpine" e quebra `wire:click` | global:160 · check-print · neo-conv:5 | ✅ |
| 2 | lesson | **`HasUuids` gera UUID v7 por padrão no Laravel 12+** (ordenável, melhor p/ índice). Para v4 usar `HasVersion4Uuids`. Regex de validação difere de v4 | global:529 | ✅ |
| 3 | error | **`ConvertEmptyStringsToNull`**: `$request->get('chave','default')` retorna `null` se a chave existir vazia — usar `?? 'default'` em vez do 2º arg | global:596 · check-print:32 | ✅ |
| 4 | error | **DomPDF + PHP 8.4**: `tempnam()` emite `E_WARNING` que o Laravel converte em exception → HTTP 500 em PDFs. Fix: `set_error_handler` suprimindo E_WARNING de `tempnam()` no `AppServiceProvider::boot()` | global:574 · check-print:18 | ➖ |
| 5 | best_practice | **`wire:key` com hash (`md5(json_encode($row))`) é obrigatório** em loops com `x-data` Alpine — sem isso o morph do Livewire preserva `x-data` antigo | global:230,344 | ✅(livewire) |
| 6 | workaround | **Downloads em páginas Livewire**: `<a href>` é interceptado pelo router SPA — adicionar atributo `download` ao link | global:434 · check-print:47 | ✅ |
| 7 | best_practice | **`wire:confirm` usa `window.confirm()` nativo** (sem dark mode / Blade dinâmico / transições) → usar modal Alpine; fechar modal ANTES de `$wire.method()` | global:257 | ✅(livewire) |
| 8 | workaround | **Blade converte `"` em `&quot;`** dentro de atributos — regex/strings em `x-data` corrompem. Fix: função global em `<script>` + `@js()` no `x-init` | global:314 | ➖ |
| 9 | error | **Expor `public $collection` Eloquent em Livewire** dispara `BadMethodCallException: getMorphClass` — hidratar no `render()`, não expor a coleção | global:410 | ➖ |
| 10 | lesson | **Tailwind v4 exige rebuild do Vite** após mudar classes/config — usar `npm run build` (não só `dev`) | global:509 | ➖ |
| 11 | lesson | **Tabelas responsivas**: `min-w-full` não ativa scroll em modais — usar `min-w-max w-full`+`overflow-x-auto` (≤3 col) ou par `lg:hidden` cards / `hidden lg:table` (4+ col) | global:362 · eventos:63 | ➖ |
| 12 | best_practice | **`[x-cloak]` no `<head>`** (CSS crítico) para modais/dropdowns não piscarem antes do Alpine init — distinto do flash de navegação (que é `@persist`) | eventos:38,42 | ✅(alpinejs) |
| 13 | best_practice | **`@persist` sidebar/header + `wire:navigate` + `wire:current`** evita recarregar menu a cada clique; `show_progress_bar => false` remove NProgress | eventos:39,40 | ✅(livewire) |
| 14 | error | **`->change()` de tipo de coluna falha no PostgreSQL** ("cannot be cast automatically to bigint") — drop+recreate em 2 `Schema::table()`; `dropIndex` antes de `dropColumn` | global:546 | ➖ |
| 15 | anti_pattern | **Nunca `DATE()`/`YEAR()` em SQL raw** (quebra cross-driver) — usar `whereDate()` ou Carbon | BellaBeaulty:27 | ✅(laravel) |
| 16 | workaround | **419 Page Expired em wizards Livewire multi-etapa** — salvar estado na session antes do redirect | BellaBeaulty:39 | ➖ |
| 17 | best_practice | **Libs JS 3rd-party (Chart.js) em Livewire**: `wire:ignore` no container + checar `typeof Chart` + bundlar via NPM (`window.Chart`) | BellaBeaulty:51 | ➖ |
| 18 | best_practice | **PHP 8.4 elevou avisos**; Laravel converte E_WARNING→exception; pacotes desatualizados (DomPDF) passam a falhar — checar `laravel.log` após upgrade | global:1448 | ✅(php) |

## TIER 2 — Arquitetura & convenções reutilizáveis

| # | tipo | afirmação (núcleo) | fontes | c7 |
|---|------|--------------------|--------|-----|
| 19 | architecture_decision | **Service é a única fonte de lógica de domínio**; Models só scopes/accessors/mutators, nunca regra de negócio | global:1649 | ➖ |
| 20 | best_practice | **FormRequest dedicado sempre** (nunca `$request->validate()` inline no controller) | global:92,487 · neo-conv:8 | ✅(laravel) |
| 21 | best_practice | **Controller = roteador**: máx 1 método público, sem queries Eloquent | global:98 | ➖ |
| 22 | best_practice | Queries com JOIN/agregação/filtro complexo → **Repositories**, não Model/Controller | global:697 | ➖ |
| 23 | best_practice | **Jobs críticos**: `$tries=3` + `$backoff=[10,60,300]` (não `$tries=1`) | global:799 | ✅(laravel) |
| 24 | best_practice | **SoftDeletes em entidades mestras**; bloquear hard-delete se houver vínculos | global:850 | ✅(laravel) |
| 25 | best_practice | **Magic numbers → `config/business.php`** lidos via `config()` | global:919 | ➖ |
| 26 | architecture_decision | **Arquitetura hexagonal**: `Contracts/` (portas) + implementações em `Services/`; DTOs `readonly` com `fromArray()`; Actions com `execute()` | global:1744,948,981 | ➖ |
| 27 | architecture_decision | **CQRS simplificado**: Commands (Actions de escrita) vs Queries (`apply(Builder)`) | global:1681 | ➖ |
| 28 | best_practice | **Filament multi-panel**: 1 `PanelProvider` por contexto; NavigationGroup via Enum `HasLabel`; Table em classe separada; RBAC `filament-shield` | global:1032,1058,1119,1245 | ➖ |
| 29 | best_practice | **PHP moderno**: `declare(strict_types=1)`, constructor property promotion, return types explícitos, `readonly class` p/ VOs | global:87,89,90,1496 | ✅(php) |
| 30 | best_practice | **Conventional Commits** (pt-BR, sem emoji, sem Co-Authored-By) — convergência em 4 projetos | skynet-conv:80 · control:87 · harness-pi:33 · global:64 | ➖ |
| 31 | best_practice | **PSR-12** + PascalCase classes / camelCase métodos — convergência em 3 projetos PHP | skynet-conv:209 · control:51 · neo-conv:4 | ➖ |
| 32 | best_practice | **TDD / testes obrigatórios no PR** (Red→Green→Refactor) — convergência em 4 projetos | context-mode:264 · aidev-conv · neo-conv · control | ➖ |
| 33 | best_practice | **Pest**: não criar arquivo de teste novo por domínio; helpers em `tests/Pest.php`; ler `__construct` antes de testar; PCOV ~5x mais rápido que Xdebug | events-clickup:16,37,68,112 · neo-conv:20 | ➖ |
| 34 | best_practice | **`final class` + Mockery = usar instância real** (não `Mockery::mock`) | events-clickup:120 | ✅(mockery) |
| 35 | best_practice | **Blade Components em `resources/views/components/neo/`** → `<x-neo.nome />`; Pint obrigatório após editar `.php`; tokens Tailwind do `@theme` (nunca cores arbitrárias) | neo-conv:7,10,13 | ✅(laravel/tailwind) |
| 36 | anti_pattern | **CRUD index sempre fullpage**; modais Alpine só p/ sub-ações no show (padrão eventos) | eventos:50,53 | ➖ |
| 37 | best_practice | **`--dry-run` + `--apply` por padrão** em comandos Artisan que escrevem em APIs externas ("safe by default") | events-clickup:86 | ➖ |

## TIER 3 — Troubleshooting de ambiente & ferramentas (reuso médio)

| # | tipo | afirmação (núcleo) | fontes | c7 |
|---|------|--------------------|--------|-----|
| 38 | workaround | **Comandos de banco em projetos Docker → sempre dentro do container** (`docker exec ... artisan migrate`); host não resolve `db`/`mysql` | BellaBeaulty:16 · global:1432 | ➖ |
| 39 | best_practice | **`storage:link` e SQLite dev via container/bind-mount** — link/volume do host aponta path errado → 403/404 | global:1314,1341 | ➖ |
| 40 | best_practice | **MCP Laravel Boost via `docker compose exec -T`** (`-T` desliga pseudo-TTY, obrigatório p/ stdio); container PHP/FPM, não nginx | global:1374 | ➖ |
| 41 | workaround | **Arquivos criados no container ficam `root:root`** (VSCode não edita) — `sudo chown -R $USER:$USER app/` | global:1293 | ➖ |
| 42 | error | **IDE reiniciando (Linux)**: limite de inotify watches — `files.watcherExclude` + vite `server.watch.ignored` + `sysctl fs.inotify.max_user_watches=524288` | repo-vedovelli:9 | ✅(vite) |
| 43 | best_practice | **TypeScript lento**: `skipLibCheck`, `incremental`, `tsBuildInfoFile`, `exclude node_modules/dist` | repo-vedovelli:118 | ✅(typescript) |
| 44 | workaround | **jq ausente em container rootless**: DEVORQ tem fallback grep/sed; instalar jq via binário do GitHub p/ saídas limpas | devorq:9 | ➖ |
| 45 | error | **Telegram polling 409 Conflict**: só 1 polling por bot — outra instância com o mesmo token ativa. Não é bug, é restrição da API | devsenior:11 | ➖ |
| 46 | anti_pattern | **Nunca `env_file:` no docker-compose** p/ Laravel — congela valores e faz `phpunit.xml` `<env>` serem ignorados → testes apontam p/ banco de dev e o apagam | nandolz:52 | ✅(laravel) |
| 47 | anti_pattern | **`docker compose down -v` apaga o volume pgdata** (bancos dev+teste juntos) — preferir `down` sem `-v` | nandolz:91 | ➖ |
| 48 | workaround | **`composer validate --strict` reclama de lock desatualizado** após editar `composer.json` → `composer update --lock` (sem atualizar deps) | nandolz:56 | ➖ |
| 49 | architecture_decision | **`php artisan serve` só repassa allowlist `ServeCommand::$passthroughVariables`** — estender no `AppServiceProvider` p/ paridade Local↔Docker das vars DB | nandolz:47 | ✅(laravel) |
| 50 | workaround | **Alpine `x-for`+`x-model` race**: valor atribuído antes das `<option>` existirem — `setTimeout` (não só `$nextTick`) | skynet-ts:12 | ➖ |
| 51 | error | **Blade**: `@class` fora do `<tr>` deixa headers invisíveis; migração automática corrompe `{{ }}` em `{ { }}` — grep antes de declarar done | eventos:61,62,65 | ✅(laravel) |

## TIER 4 — Nicho / project-specific (baixo reuso; promover só sob demanda)

| # | tema | afirmação (núcleo) | fontes | c7 |
|---|------|--------------------|--------|-----|
| 52 | SQL legado (skynet) | Prefixação obrigatória de campos em joins (`f.valorBase`); validar coluna via `DESCRIBE` antes de subquery; `Undefined index` PHP quebra JSON de endpoint AJAX | skynet-refat:5,10,16 | ✅(mysql/php) |
| 53 | Scraping governamental | Regex >> DomCrawler p/ HTML gov ISO-8859-1 (2MB); fallback hardcoded; `mb_convert_encoding` obrigatório; SRP scraper vs comparador | global:1589,1618,1631 | ➖ |
| 54 | ClickUp API | Rate limit 100 req/min — cache + batch | events-clickup:157 | ➖ |
| 55 | DEVORQ interno | ~14 pares problema→solução do orquestrador (PATH, gates, context.json, sync push/pull, shellcheck) | devorq:28–276 | ➖ |
| 56 | Hermes/devsenior | jq fail-closed no commit gate; distinção distribution-owned vs user-owned em `install --force`; skill não recarrega sem reinstall | devsenior:28,54,79 | ➖ |
| 57 | context-mode (MCP) | **Anti-padrão SQLite multi-writer**: não adicionar file locks nem `locking_mode=EXCLUSIVE`; confiar em `busy_timeout`+retry. Não registrar hooks em `settings.json` E `hooks.json` (dupla invocação) | context-mode:85,157 | ✅(sqlite) |
| 58 | Renovate | Requer app habilitado no GitHub; agenda sábado de manhã (America/Sao_Paulo) | nandolz:95 | ➖ |

---

## Recomendação de promoção

- **Promover (alta confiança):** Tier 1 (#1–18) — são o núcleo Laravel/Livewire/PHP/Tailwind, exatamente o stack do hub, várias já validadas ✅ e cross-confirmadas em múltiplos projetos.
- **Promover (arquitetura/convenções):** Tier 2 (#19–37) — reutilizáveis; #30–32 (Commits/PSR-12/TDD) têm convergência forte entre projetos → bons candidatos a `best_practice` global.
- **Revisar antes:** as peças `➖` de Tier 1/2 (validar contra Context7 na promoção) e itens fortes mas afirmativos (#14 `->change()` Postgres, #19 "Service única fonte").
- **Promover sob demanda:** Tier 3 (útil como troubleshooting) e Tier 4 (nicho — só se o projeto de origem for reativado).
- **Descartar:** os 9 `lessons.md` stub; as cópias near-dup (F2/nando-lz, nando-events-main, `events/archived` marcado "referência histórica").

## Configs de harness capturáveis (Fase 4, via `harness_capture`)

Encontrados nos projetos: **25 `CLAUDE.md`, 30 `.mcp.json`, 2 harness_global, 17 linters, 43 editorconfig**. Não inventariados peça-a-peça aqui — capturar via `harness_capture` quando o hub estiver conectado.

## Próximo passo (quando o hub MCP estiver conectado)

1. Reabrir esta escavação; o usuário aprova o conjunto (por # / faixa).
2. `memory_ingest` uma chamada por peça aprovada — `source=excavation`, `trigger=<tipo>`, `project=<origem>`.
3. `harness_paths` + `harness_capture` para os `CLAUDE.md`/`.mcp.json` desejados.
4. Revisar as memórias **pendentes** na UI do hub (sanitização/dedup/curadoria são do hub).
