# Catálogo de Tools MCP — Dev Memory Hub

O MCP é o **único caminho programático oficial** para o hub. Todo acesso é tokenizado.

## Transportes

| Transporte | Uso | Autenticação |
|------------|-----|--------------|
| **stdio** | Local, mesmo host (`php artisan mcp:serve`) | processo local |
| **HTTP** | Remoto, outros projetos (`POST /api/mcp`) | token de API (Bearer) |

Ambos expõem o mesmo `MemoryMcpServer` e as mesmas 11 tools.

### Conectar via HTTP (outro projeto)

Gere um token na UI (**MCP_TOKENS**) e configure o `.mcp.json` do projeto consumidor:

```json
{
  "mcpServers": {
    "dev-memory": {
      "type": "http",
      "url": "https://SEU-HUB/api/mcp",
      "headers": { "Authorization": "Bearer <SEU_TOKEN>" }
    }
  }
}
```

### Conectar via stdio (local)

```json
{
  "mcpServers": {
    "dev-memory": { "command": "php", "args": ["artisan", "mcp:serve"] }
  }
}
```

---

## Tools (11)

### Leitura

| Tool | Argumentos | Retorno |
|------|-----------|---------|
| `memory_list` | `type?`, `scope?`, `stack?`, `limit?=20` | lista de memórias filtrada |
| `memory_search` | `query` (obrig.), `limit?=10` | busca em título/descrição, ordenada por recorrência |
| `memory_get` | `id` (obrig.) | detalhes completos de uma memória |
| `memory_stats` | — | totais por tipo, escopo e top stacks |

### Escrita

| Tool | Argumentos | Comportamento |
|------|-----------|---------------|
| `memory_create` | `title`, `description`, `type` (obrig.), `stack?`, `scope?=project` | cria memória |
| `memory_update` | `id` (obrig.), `title?`, `description?`, `type?`, `stack?`, `scope?` | atualiza campos informados (valida enums) |
| `memory_validate` | `id` (obrig.) | marca como validada |
| `memory_promote` | `id` (obrig.) | promove a global (exige estar validada) |
| `memory_delete` | `id` (obrig.), `confirmation_token?` | **destrutiva — ver fluxo abaixo** |

### Inteligência

| Tool | Argumentos | Retorno |
|------|-----------|---------|
| `hub_briefing` | `stack?`, `description?` | consulta preventiva: riscos conhecidos, padrões aprovados, lições relevantes e skills para o contexto — **use ANTES de implementar** |
| `memory_ingest` | `content` (obrig.), `source?=mcp`, `trigger?`, `project?` | ingere evento bruto no pipeline (sanitiza, deduplica, enfileira curadoria) |

---

## Ação destrutiva: fluxo de confirmação (`memory_delete`)

`memory_delete` exige confirmação em duas fases (via `ConfirmationGuard`):

1. **1ª chamada** (só `id`) → retorna `requires_confirmation: true`, um `preview` do que será apagado e um `confirmation_token`.
2. **2ª chamada** (`id` + `confirmation_token`) → executa o **soft-delete** (recuperável).

Garantias do token: **single-use**, **target-bound** (o token de A não apaga B) e **TTL de 5 minutos**. Token inválido/expirado retorna erro; nada é apagado sem confirmação válida.

```jsonc
// 1ª chamada
{ "method": "tools/call", "params": { "name": "memory_delete", "arguments": { "id": "<uuid>" } } }
// → { "requires_confirmation": true, "preview": {...}, "confirmation_token": "abc..." }

// 2ª chamada
{ "method": "tools/call", "params": { "name": "memory_delete",
  "arguments": { "id": "<uuid>", "confirmation_token": "abc..." } } }
// → { "success": true, "message": "Memória removida (soft-delete, recuperável)." }
```

---

## Gestão de tokens

Na UI, seção **MCP_TOKENS** (`/admin/tokens`): emitir (o texto é exibido **uma única vez**, apenas o hash SHA-256 é persistido), ver último uso e revogar. Tokens são escopados por usuário.
