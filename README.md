# Dev Memory

Sistema de memória técnica para capturar, organizar e reutilizar aprendizados de desenvolvimento.

## Stack

- **Backend**: Laravel 13 + PHP 8.3
- **Frontend**: Livewire 4 + Tailwind CSS 4
- **Banco de dados**: SQLite (dev) / PostgreSQL/MySQL (prod)
- **Protocolo**: MCP (Model Context Protocol) para integração com IAs

## Arquitetura

```
app/
├── Enums/
│   ├── MemoryScope.php       # Escopo: project | global
│   ├── MemoryType.php       # Tipo: error | lesson | best_practice
│   └── ValidationStatus.php # Status: pending | validated | rejected
├── Livewire/
│   ├── Dashboard.php         # Página inicial com estatísticas
│   ├── MemoryForm.php        # Formulário criar/editar memória
│   ├── MemoryList.php        # Listagem com filtros
│   └── MemoryDetail.php      # Visualização detalhada
├── Models/
│   └── Memory.php            # Modelo principal com scopes
├── Services/
│   └── MemoryService.php     # Lógica de negócio
└── Mcp/
    └── MemoryMcpServer.php    # Servidor MCP para IAs
```

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

## Rotas

| Método | URI | Componente | Descrição |
|--------|-----|------------|-----------|
| GET | `/` | Dashboard | Visão geral com estatísticas |
| GET | `/memories` | MemoryList | Listar memórias com filtros |
| GET | `/memories/create` | MemoryForm | Criar nova memória |
| GET | `/memories/{id}` | MemoryDetail | Ver detalhes |
| GET | `/memories/{id}/edit` | MemoryForm | Editar memória |

## Filtros Disponíveis

- **type**: error, lesson, best_practice
- **stack**: busca parcial (ILIKE)
- **scope**: project, global
- **search**: busca em título e descrição

## MCP Tools

O servidor MCP permite que IAs interajam com o sistema:

```json
{
  "memory_list": "Lista memórias com filtros",
  "memory_search": "Busca por texto em título/descrição",
  "memory_get": "Retorna detalhes de uma memória",
  "memory_create": "Cria nova memória",
  "memory_stats": "Estatísticas gerais"
}
```

### Configuração MCP

Adicione ao `.mcp.json` do seu projeto:

```json
{
  "mcpServers": {
    "dev-memory": {
      "command": "php",
      "args": ["artisan", "mcp:serve"]
    }
  }
}
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

```bash
# Servidor local
php artisan serve

# Migrar banco
php artisan migrate

# Resetar banco (dev)
php artisan migrate:fresh --seed

# Servidor MCP standalone
php artisan mcp:serve
```

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
