# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This App Does

**Dev Memory** is a knowledge base for capturing and reusing development learnings (errors, lessons, best practices) with MCP (Model Context Protocol) integration for AI assistants. Memories have type, scope (project/global), stack tag, and validation status (pending → validated/rejected).

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
HTTP Request → Livewire Component → MemoryService → Memory Model (Eloquent) → DB
                                         ↑
MCP Request → McpServeCommand → MemoryMcpServer → MemoryService
```

### Key Layers

- **`app/Enums/`** — `MemoryType` (error/lesson/best_practice), `MemoryScope` (project/global), `ValidationStatus` (pending/validated/rejected)
- **`app/Models/Memory.php`** — UUID PK, Eloquent scopes (filter, byType, byScope, validated, etc.)
- **`app/Services/MemoryService.php`** — All business logic: CRUD, search, stats, scope promotion
- **`app/Livewire/`** — Four full-page components: `Dashboard`, `MemoryList`, `MemoryForm`, `MemoryDetail`
- **`app/Mcp/MemoryMcpServer.php`** — MCP protocol with 5 tools: `memory_list`, `memory_search`, `memory_get`, `memory_create`, `memory_stats`
- **`resources/views/components/neo/`** — 12 reusable Blade UI components (neo brutalist design system)

### Routes
All routes (`routes/web.php`) use Livewire full-page components:
- `GET /` → Dashboard
- `GET /memories` → MemoryList (reactive search/filter)
- `GET /memories/create` → MemoryForm
- `GET /memories/{memory}` → MemoryDetail
- `GET /memories/{memory}/edit` → MemoryForm

### MCP Integration
`.mcp.json` configures `php artisan mcp:serve` as MCP server. See global CLAUDE.md for Docker MCP config patterns if running in containers.

## Testing

PHPUnit with SQLite in-memory. Tests live in:
- `tests/Unit/` — Model scopes (`MemoryModelTest`), service logic (`MemoryServiceTest`)
- `tests/Feature/` — API endpoints (`MemoryApiTest`)

Environment for tests is set in `phpunit.xml` (array cache, sync queue, SQLite in-memory).

## Environment Files

- `.env.example` — Local dev defaults (SQLite, database cache/queue)
- `.env.docker` — Docker defaults (PostgreSQL, Redis cache/queue/session)
