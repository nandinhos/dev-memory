# SPEC: dev-memory-laravel ↔ VPS HUB Integration

**Versão:** 0.1.0  
**Projeto:** dev-memory-laravel  
**Data:** 2026-04-21  
**Status:** brainstorming  
**Autor:** Nando / DEVORQ  

---

## 1. Contexto & Visão

### O que é
O **VPS HUB** (srv163217:6985, `/var/devorq/hub/`) é o centro de memória persistente com:
- 25 skills DEVORQ categorizadas por stack
- SPECs e documentação
- Histórico de lessons aprendidas

O **dev-memory-laravel** é a **interface visual** desse hub — um frontend onde:
- Desenvolvedores navegam, criam e validam memórias via UI
- AI assistants consultam o hub via MCP (JSON-RPC over stdio)

### Arquitetura Almejada
```
┌─────────────────────────────────────────────────────┐
│  VPS HUB (srv163217)                                │
│  /var/devorq/hub/                                   │
│  ├── skills/      ← 25 skills por stack              │
│  ├── lessons/    ← lessons aprendidas               │
│  └── memories/    ← (NEW) storage de memórias        │
└─────────────────────────────────────────────────────┘
         ▲ SSH (leitura)
         │ via MCP bridge
         ▼
┌─────────────────────────────────────────────────────┐
│  dev-memory-laravel (MCP Server)                    │
│  ┌───────────────────────────────────────────────┐  │
│  │  MemoryMcpServer (stdio JSON-RPC)             │  │
│  │  • Tools: list, search, get, create, stats    │  │
│  │  • READS from VPS HUB via SSH                 │  │
│  │  • WRITES to local PostgreSQL                 │  │
│  └───────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────┐  │
│  │  Livewire UI (Dashboard, MemoryList, etc.)   │  │
│  │  • Visualiza memórias locais                  │  │
│  │  • Mostra skills disponíveis no HUB          │  │
│  └───────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
```

### Benefícios
- AI assistants (Hermes, Claude Code, Codex) consultam o hub via MCP sem precisar de SSH manual
- Interface visual para devs não técnicos navegarem o conhecimento
- Memórias validadas na UI são sincronizadas de volta para o VPS
- Skills DEVORQ v3 ficam acessíveis a qualquer AI assistant via MCP

---

## 2. Funcionalidades

### FASE 1 — VPS como fonte de leitura (MVP)
O MCP server lê skills e lessons do VPS HUB via SSH, SEM modificar o código atual do dev-memory-laravel.

**Nova tool MCP:**
- `hub_skills_list` — lista skills disponíveis no VPS HUB
- `hub_skills_search` — busca skills por tag/stack
- `hub_lessons_list` — lista lessons aprendidas no VPS

**Fluxo:**
```
AI Assistant
  → MCP: hub_skills_search {stack: "laravel"}
  → dev-memory-laravel (MCP bridge)
  → SSH → VPS HUB (skills/)
  → JSON response
  → AI Assistant
```

**Interface UI (nova aba "HUB"):**
- Lista de skills do VPS HUB (readonly)
- Link para VS Code/editor para editar skill

### FASE 2 — Bidirectional Sync
Memórias criadas na UI do dev-memory-laravel são sincronizadas para `/var/devorq/hub/memories/` no VPS.

**Fluxo:**
```
Dev cria memória na UI
  → saved to local PostgreSQL
  → SSH push → VPS HUB (memories/)
  → Hub atualizado
```

### FASE 3 — VPS como cache vetorial (futuro)
Skills indexadas por embedding para busca semântica via AI.

---

## 3. Requisitos Técnicos

### 3.1 Storage no VPS
```bash
/var/devorq/hub/
├── skills/          # 25 skills (ja existe)
├── lessons/         # lessons aprendidas (ja existe)
├── memories/        # NOVO: memórias exportadas
│   ├── pending/
│   ├── validated/
│   └── rejected/
└── sync.lock        # lock file para evitar conflitos
```

### 3.2 MCP Bridge (dev-memory-laravel)
- Arquivo novo: `app/Mcp/VpsHubBridge.php`
- Faz SSH para VPS e lê arquivos JSON
- Mantém cache local (Redis ou arquivo) com TTL de 5 min

### 3.3 Nova tool no MCP Server
```php
// app/Mcp/MemoryMcpServer.php — adicionar
'hub_skills_list' => [...],
'hub_skills_search' => [...],
'hub_lessons_list' => [...],
```

### 3.4 Interface UI (Livewire)
- Nova aba "HUB" no Dashboard
- Lista skills do VPS HUB
- Mostra stats (total skills, lessons, memories)
- Status de conexão com VPS (verde/vermelho)

---

## 4. Dependências

| Dependência | Status | Notas |
|-------------|--------|-------|
| PHP 8.2+ | ✅ disponivel (no container) | |
| Laravel 13 | ✅ ja no projeto | |
| SSH2 extension | ⚠️ precisa verificar | Ou usar `ssh` command via exec |
| PostgreSQL | ✅ ja configurado | |
| Redis | ✅ ja configurado | Para cache do bridge |

---

## 5. Endpoints do VPS HUB (via SSH)

### Ler skills
```bash
ssh -p 6985 root@187.108.197.199 \
  "find /var/devorq/hub/lessons -name 'SKILL.md' -exec grep -l 'tags.*laravel' {} \;"
```

### Ler lessons
```bash
ssh -p 6985 root@187.108.197.199 \
  "find /var/devorq/hub/lessons/.devorq/skills -name '*.json' -path '*/applied/*'"
```

### Escrever memória
```bash
ssh -p 6985 root@187.108.197.199 \
  "cat > /var/devorq/hub/memories/pending/{uuid}.json <<< '{...}'"
```

---

## 6. Gates (DEVORQ Flow)

| Gate | Critério | Status |
|------|----------|--------|
| GATE-1 | SPEC approved | pending |
| GATE-2 | Arquitetura validada (lib/detect.sh + VpsHubBridge.php) | pending |
| GATE-3 | MVP funcionando (hub_skills_list via MCP) | pending |
| GATE-4 | UI mostra skills do VPS (HUB tab) | pending |
| GATE-5 | Sync bidirecional funcionando | pending |
| GATE-6 | Testes passando | pending |
| GATE-7 | Lessons learned documentadas | pending |

---

## 7. Notas

- **SSH multiplexing** já configurado em `/tmp/devorq-hub.sock` — usar `ssh -S /tmp/devorq-hub.sock`
- **Caching:** Redis ja disponível no Docker do projeto — usar para cachear respostas do VPS com TTL 5min
- **Error handling:** se VPS offline, MCP retorna `[]` com warning, não bloqueia fluxo
- **Performance:** SSH multiplexing mantém socket aberto — latência ~0.3s por query
