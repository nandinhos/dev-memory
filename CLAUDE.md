# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This App Does

**Dev Memory** is a knowledge hub for capturing, curating and reusing development learnings (errors, lessons, best practices, workarounds, architecture decisions, anti-patterns). It is an **authenticated web app** (neo design system) plus a **remote MCP server** so any project connects over the network with a token. Memories flow through an async curation pipeline: external capture → sanitize → curate (MiniMax structured output) → documentation validation (Context7) → recurrence dedup → group & compile into versioned Skills (git-backed). The web UI administers the whole pipeline; programmatic access is MCP-only.

## Commands

### Local Development (SQLite)
```bash
composer setup        # Initial setup: install, migrate, npm
composer dev          # Start all services concurrently (server, queue, logs, Vite)
composer test         # Run full PHPUnit suite (clears config first)
npm run build         # Vite production build
npm run dev           # Vite dev server only
```

### Docker Development (PostgreSQL + Redis)
```bash
cp .env.docker .env
make build && make up   # Build and start containers (port 9587)
make test               # Run full PHPUnit suite in container
make test-unit          # Unit tests only
make test-feature       # Feature tests only
make migrate            # Run migrations
make shell              # Enter app container
make down               # Stop containers
make clean              # Full cleanup (containers, volumes, images)
```

### Code Quality
```bash
./vendor/bin/pint       # Run Laravel Pint linter
./vendor/bin/pint --test  # Check without fixing
```

### Single Test
```bash
php artisan test --filter TestClassName
php artisan test tests/Unit/MemoryServiceTest.php
```

## Architecture

**Stack:** Laravel 13 + Livewire 4.2 + Tailwind CSS 4 + Vite. PostgreSQL 16 + Redis 7 in Docker; SQLite for local/testing.

### Data Flow
```
Web (auth) → Livewire Component → MemoryService → Memory Model → DB
MCP local (stdio)  → McpServeCommand ┐
MCP remote (HTTP)  → McpController   ┴→ MemoryMcpServer → MemoryService / Curation
External capture → memory_ingest → CaptureService → CurateCaptureJob (fila) → Memory
```
Programmatic access is **MCP only** (the legacy `/api/memories` REST API was removed). Both MCP transports are authenticated; HTTP uses API tokens (`ApiToken`, `/admin/tokens`).

### Key Layers

- **`app/Enums/`** — `MemoryType` (error/lesson/best_practice/workaround/architecture_decision/anti_pattern), `MemoryScope`, `ValidationStatus`, `DocumentationValidationStatus`, `MemorySource`, `Severity`, `CaptureStatus`, `SkillGroupStatus`, `SkillStatus`
- **`app/Models/`** — `Memory` (UUID PK, scopes incl. `skillCandidates`), `Capture`, `CurationExecution`, `SkillGroup`, `Skill`, `ApiToken`
- **`app/Services/MemoryService.php`** — CRUD, search, stats, promotion, dedup (`findSimilarByTitle`)
- **`app/Services/HubBriefingService.php`** — preventive briefing aggregation; **`ConfirmationGuard`** — two-phase confirmation for destructive MCP actions
- **`app/Services/Curation/`** — the pipeline: `CaptureService`+`CaptureSanitizer` (ingest/scrub), `AnthropicCurationEngine` (MiniMax structured output + repair), `DocumentationValidator`+`Context7Client` (grounded validation), `RecurrenceScorer`, `PromotionPolicy`, `SkillGroupProposer`, `SkillCompiler`, `SkillPublisher` (git)
- **`app/Jobs/`** — `CurateCaptureJob`, `ValidateMemoryDocumentationJob` (async pipeline)
- **`app/Livewire/`** — `Auth/Login`; memory pages (`Dashboard`, `MemoryList`, `MemoryForm`, `MemoryDetail`); `Admin/` (`CapturesInbox`, `SkillGroupsReview`, `SkillsAdmin`, `ApiTokens`)
- **`app/Mcp/MemoryMcpServer.php`** — MCP with **15 tools** (stdio + HTTP). See `docs/mcp-tools.md` for the full catalog. `memory_delete` is destructive (two-phase confirmation)
- **`app/Http/`** — `McpController` (remote MCP over HTTP), `AuthenticateMcpToken` middleware (`mcp.token`)
- **`resources/views/components/neo/`** — reusable Blade UI components (neo brutalist design system). **Rebuild assets after CSS/Blade changes** (`npm run build` or `npm run dev`) — serving stale build breaks layout/scroll.

### Routes
Web (`routes/web.php`) — `GET /login` (guest); everything else under `auth` middleware:
- `GET /` → Dashboard; `/memories/*` → memory pages
- `/admin/captures`, `/admin/skill-groups`, `/admin/skills`, `/admin/tokens` → pipeline admin
- `POST /logout`

API (`routes/api.php`) — `POST /api/mcp` (remote MCP, `mcp.token` middleware).

### MCP Integration
`.mcp.json` configures `php artisan mcp:serve` (stdio, local). For remote access, other projects hit `POST /api/mcp` with a Bearer API token issued at `/admin/tokens`. Full tool catalog + connection config in `docs/mcp-tools.md`. See global CLAUDE.md for Docker MCP config patterns if running in containers.

### Hub commands
`memory:make-admin`, `memory:import`, `memory:process-captures`, `memory:validate-docs`, `memory:group-skills`, `memory:compile-skills`, `memory:publish-skills`, `memory:curate`. Curation engine uses MiniMax (`MINIMAX_API_KEY`); doc validation uses Context7 (`CONTEXT7_API_KEY`, optional).

## Testing

PHPUnit with SQLite in-memory (~146 tests). Tests live in:
- `tests/Unit/` — model scopes/service logic, and Curation contracts (`LessonDraft`, `DocumentationVerdict`, `SkillCandidate`, `SkillGroupProposal`), `CaptureSanitizer` (zero secret leak), `PromotionPolicy`, `Context7Client`
- `tests/Feature/` — pipeline (`CaptureServiceTest`, `CurateCaptureJobTest`, `ValidateMemoryDocumentationJobTest`, `SkillCompilerTest`, `RecurrenceScorerTest`), MCP (`McpHttpTest`, `McpWriteToolsTest`, `HubBriefingTest`), auth (`AuthTest`), admin (`AdminPipelineTest`)

Engine/HTTP tests fake the MiniMax/Context7 calls (`Http::fake`). Environment set in `phpunit.xml` (array cache, sync queue, SQLite in-memory).

## Environment Files

- `.env.example` — Local dev defaults (SQLite, database cache/queue)
- `.env.docker` — Docker defaults (PostgreSQL, Redis cache/queue/session)
