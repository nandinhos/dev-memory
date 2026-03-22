# Dev-Memory - Plano de Implementação

## ✅ Concluído (MVP v1.0)

### Backend
- [x] Laravel 13 + Livewire 4
- [x] Migration `create_memories_table`
- [x] Model `Memory` com Enums (type, scope, validation_status)
- [x] Service `MemoryService` (CRUD, search, stats)
- [x] API REST Controller com endpoints completos
- [x] Request validation

### Frontend
- [x] Livewire `MemoryList` (listagem com filtros)
- [x] Livewire `MemoryForm` (create/edit)
- [x] Livewire `MemoryDetail` (visualização)
- [x] Views Blade básicas

### Infraestrutura
- [x] Rotas web.php e api.php configuradas
- [x] Arquitetura de diretórios seguir PRD
- [x] Design tokens `.architect/` integrados

---

## 📋 Próximas Etapas

### Fase 2: Melhorias UI/UX

**Prioridade:** Média

- [ ] Instalar e configurar TailwindCSS
- [ ] Melhorar layout das views (cards, badges, cores)
- [ ] Adicionar paginação customizada
- [ ] Implementar busca em tempo real

### Fase 3: Banco de Dados

**Prioridade:** Alta

- [ ] Configurar PostgreSQL no .env
- [ ] Executar migrations em PostgreSQL
- [ ] Adicionar seeders com dados de exemplo
- [ ] Configurar PGVector para busca semântica (futuro)

### Fase 4: MCP Server

**Prioridade:** Alta

- [ ] Testar comando `php artisan mcp:serve`
- [ ] Configurar STDIO em vez de socket
- [ ] Integrar com Claude Code via .mcp.json
- [ ] Documentar tools disponíveis

### Fase 5: Funcionalidades Extras

**Prioridade:** Baixa

- [ ] Sistema de validação para escopo global
- [ ] Dashboard com estatísticas
- [ ] Importação/exportação de memórias
- [ ] Histórico de alterações

---

## Estrutura de Arquivos

```
dev-memory-laravel/
├── app/
│   ├── Enums/
│   │   ├── MemoryType.php
│   │   ├── MemoryScope.php
│   │   └── ValidationStatus.php
│   ├── Models/
│   │   └── Memory.php
│   ├── Services/
│   │   └── MemoryService.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── MemoryController.php
│   │   └── Requests/
│   │       └── StoreMemoryRequest.php
│   ├── Livewire/
│   │   ├── MemoryList.php
│   │   ├── MemoryForm.php
│   │   └── MemoryDetail.php
│   ├── Mcp/
│   │   └── MemoryMcpServer.php
│   └── Console/
│       └── Commands/
│           └── McpServeCommand.php
├── database/
│   └── migrations/
│       └── 2026_03_22_000001_create_memories_table.php
├── routes/
│   ├── web.php
│   └── api.php
├── resources/
│   └── views/
│       ├── livewire/
│       │   ├── memory-list.blade.php
│       │   ├── memory-form.blade.php
│       │   └── memory-detail.blade.php
│       └── layouts/
│           └── app.blade.php
├── .architect/
│   ├── config.json
│   ├── tokens.json
│   └── rules/
├── .mcp.json
└── docs/
    ├── PRD.md
    └── plans/
        └── implementation-plan.md
```

---

## Endpoints API

| Method | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/memories` | Lista memórias (paginação) |
| POST | `/api/memories` | Cria memória |
| GET | `/api/memories/{id}` | Detalha memória |
| PUT | `/api/memories/{id}` | Atualiza memória |
| DELETE | `/api/memories/{id}` | Remove memória |
| GET | `/api/memories/search?q=` | Busca por texto |
| POST | `/api/memories/{id}/validate` | Valida memória |
| POST | `/api/memories/{id}/promote` | Promove para global |
| GET | `/api/stats` | Estatísticas |

---

## Variáveis de Ambiente (.env)

```env
# Banco de dados
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=dev_memory
DB_USERNAME=postgres
DB_PASSWORD=

# Aplicação
APP_URL=http://localhost:8000
```

---

## Comandos Úteis

```bash
# Servidor de desenvolvimento
php artisan serve

# MCP Server (STDIO)
php artisan mcp:serve

# Limpar cache
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Migrations
php artisan migrate
php artisan migrate:fresh

# criar nova memória via API
curl -X POST http://localhost:8000/api/memories \
  -H "Content-Type: application/json" \
  -d '{"title":"Erro X","description":"Descrição","type":"error","stack":"Laravel"}'
```

---

## Tecnologias

- **Backend:** Laravel 13 + Livewire 4
- **Banco:** PostgreSQL (SQLite por padrão)
- **Frontend:** Blade + TailwindCSS (futuro)
- ** IA:** MCP Server (STDIO)
- **Design:** Architect Tokens

---

*Última atualização: 22/03/2026*