# Dev Memory

Sistema de memória técnica para capturar, organizar e reutilizar aprendizados de desenvolvimento.

## Stack

- **Backend**: Laravel 13 + PHP 8.3
- **Frontend**: Livewire 4 + Tailwind CSS 4
- **Banco de dados**: PostgreSQL 16
- **Cache/Queue**: Redis 7
- **Protocolo**: MCP (Model Context Protocol) para integração com IAs
- **Container**: Docker + Docker Compose

## Arquitetura

```
app/
├── Enums/                    # MemoryType (6 tipos), MemoryScope, ValidationStatus,
│                             # DocumentationValidationStatus, MemorySource, Severity,
│                             # CaptureStatus, SkillGroupStatus, SkillStatus
├── Livewire/
│   ├── Auth/Login.php        # Login (design neo)
│   ├── Dashboard/List/Form/Detail.php   # Gestão de memórias
│   └── Admin/                # Painel do pipeline: CapturesInbox, SkillGroupsReview,
│                             # SkillsAdmin, ApiTokens
├── Models/                   # Memory, Capture, CurationExecution, SkillGroup, Skill, ApiToken
├── Services/
│   ├── MemoryService.php     # CRUD e regras de negócio
│   ├── HubBriefingService.php # Consulta preventiva (briefing)
│   ├── ConfirmationGuard.php # Confirmação de ações destrutivas
│   └── Curation/             # Pipeline: CaptureService, CaptureSanitizer,
│                             # AnthropicCurationEngine (MiniMax), DocumentationValidator
│                             # (Context7), RecurrenceScorer, PromotionPolicy,
│                             # SkillGroupProposer, SkillCompiler, SkillPublisher
├── Jobs/                     # CurateCaptureJob, ValidateMemoryDocumentationJob
├── Http/
│   ├── Controllers/McpController.php     # Endpoint MCP remoto (HTTP)
│   └── Middleware/AuthenticateMcpToken.php
└── Mcp/MemoryMcpServer.php   # Servidor MCP (11 tools, stdio + HTTP)
```

Pipeline de curadoria (P1–P6): captura imutável → sanitização determinística →
curadoria com structured output (MiniMax) → política de promoção → recorrência composta →
validação documental (Context7) → agrupamento e compilação de skills com rastreabilidade de fonte →
publicação em repositório git. Detalhes em `docs/plans/essencia-inicial.md`.

## Modelo de Dados

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | UUID | Identificador único |
| `project_id` | UUID | Projeto opcional (futuro) |
| `title` | text | Título da memória |
| `description` | text | Descrição detalhada |
| `type` | enum | error, lesson, best_practice |
| `stack` | string | Stack técnica (Laravel, Vue, etc) |
| `scope` | enum | project ou global |
| `validation_status` | enum | pending, validated, rejected |
| `official_reference` | text | Link/documento oficial |
| `recurrence_count` | int | Contagem de reutilizações |

## Autenticação

Todo o app web exige login (`/login`, design neo). Crie o admin com:

```bash
php artisan memory:make-admin
```

As rotas web ficam sob middleware `auth`; guests são redirecionados para `/login`.

## Rotas

| Método | URI | Descrição |
|--------|-----|-----------|
| GET | `/` | Landing page (pública) |
| GET | `/login` | Login (guest) |
| POST | `/logout` | Logout |
| GET | `/dashboard` | Dashboard |
| GET | `/memories`, `/memories/create`, `/memories/{id}`, `/memories/{id}/edit` | Gestão de memórias |
| GET | `/admin/captures` | Inbox de captures do pipeline |
| GET | `/admin/skill-groups` | Revisão de agrupamentos de skills |
| GET | `/admin/skills` | Gestão de skills (draft → aprovada → publicada) |
| GET | `/admin/tokens` | Emissão/revogação de tokens MCP |
| GET | `/admin/harness` | Perfis de config de harness (provisionamento) |
| POST | `/api/mcp` | Endpoint MCP remoto (token de API) |

## MCP Tools

O MCP é o **único caminho programático oficial** — tokenizado, com transportes stdio (local)
e HTTP (remoto). São **11 tools**: leitura (`memory_list/search/get/stats`), escrita
(`memory_create/update/validate/promote/delete`) e inteligência (`hub_briefing`, `memory_ingest`).

`memory_delete` é destrutiva e exige confirmação em duas fases (preview + token single-use).

📖 **Catálogo completo, argumentos e fluxo de confirmação: [`docs/mcp-tools.md`](docs/mcp-tools.md).**

### Configuração MCP

Local (stdio):
```json
{ "mcpServers": { "dev-memory": { "command": "php", "args": ["artisan", "mcp:serve"] } } }
```

Remoto (HTTP, outro projeto) — gere o token na UI (**MCP_TOKENS**):
```json
{ "mcpServers": { "dev-memory": {
  "type": "http", "url": "https://SEU-HUB/api/mcp",
  "headers": { "Authorization": "Bearer <SEU_TOKEN>" }
} } }
```

## Componentes UI (Neo Design System)

Localização: `resources/views/components/neo/`

| Componente | Uso |
|------------|-----|
| `x-neo::button` | Botões com variantes |
| `x-neo::input` | Campos de texto |
| `x-neo::select` | Dropdowns |
| `x-neo::textarea` | Campos de texto longo |
| `x-neo::badge` | Tags com cores |
| `x-neo::card` | Cards container |
| `x-neo::alert` | Mensagens de alerta |
| `x-neo::empty-state` | Estado vazio |
| `x-neo::memory-card` | Card especializado para memórias |
| `x-neo::code-block` | Bloco de código com highlight |
| `x-neo::content-block` | Bloco de conteúdo formatado |

## Setup Local

### Com Docker (Recomendado)

```bash
# Copiar variáveis de ambiente
cp .env.docker .env

# Build e start dos containers
make build
make up

# Ou diretamente:
docker compose up -d

# Acessar em http://localhost:9587
```

### Sem Docker

```bash
# Instalar dependências
composer setup

# Ou manualmente:
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build

# Executar
composer dev
```

## Comandos Úteis

### Makefile (Docker)

```bash
make up          # Iniciar containers
make down        # Parar containers
make restart     # Reiniciar containers
make logs        # Ver todos os logs
make shell       # Entrar no container
make migrate     # Executar migrations
make seed        # Seed database
make test        # Rodar testes
make clean       # Remover containers e volumes
```

### Artisan (dentro do container)

```bash
# Shell no container
docker compose exec app bash

# Servidor local (sem Docker)
php artisan serve

# Migrar banco
php artisan migrate

# Resetar banco (dev)
php artisan migrate:fresh --seed

# Servidor MCP standalone
php artisan mcp:serve
```

### Comandos do Hub (pipeline de curadoria)

```bash
php artisan memory:make-admin          # Cria/redefine o admin do hub
php artisan memory:import <fonte>      # Importa memórias de fontes locais
php artisan memory:process-captures    # Despacha curadoria das captures pendentes
php artisan memory:validate-docs       # Validação documental via Context7
php artisan memory:group-skills        # Propõe agrupamentos de candidatas a skill
php artisan memory:compile-skills      # Compila grupos aprovados em skills (draft)
php artisan memory:publish-skills      # Publica skills aprovadas no repo git
php artisan memory:curate --source=eval # Piloto de curadoria (medição de qualidade)
```

> O motor de curadoria usa MiniMax (API compatível com Anthropic) — configure `MINIMAX_API_KEY` no `.env`.
> A validação documental usa Context7 (free tier funciona sem chave; `CONTEXT7_API_KEY` sobe limites).

## Environment

| Variável | Descrição | Padrão |
|----------|-----------|--------|
| `APP_ENV` | Ambiente | local |
| `DB_CONNECTION` | Driver DB | sqlite |
| `DB_DATABASE` | Caminho do banco | database/database.sqlite |

## Fluxo de Trabalho

1. **Criar**: Capture um erro, lição ou boa prática
2. **Validar**: Revise memórias pendentes
3. **Promover**: Memórias validadas podem ser globais
4. **Reutilizar**: Busque soluções já documentadas

## Desenvolvimento

- Use `php artisan test` para rodar testes
- Use `composer dev` para iniciar todos os serviços simultaneamente
