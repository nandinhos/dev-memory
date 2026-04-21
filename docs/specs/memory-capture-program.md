# SPEC: Memory Capture Program — Catálogo Global de Conhecimento

**Versão:** 0.1.0  
**Projeto:** dev-memory-laravel + VPS HUB  
**Data:** 2026-04-21  
**Status:** brainstorming  
**Autor:** Nando  

---

## 1. Visão Geral

### O que é
Programa sistemático para **capturar, catalogar, padronizar e validar** TODAS as lições aprendidas de todos os projetos de Nando — extraindo de múltiplas fontes, centralizando no dev-memory-laravel (frontend visual) + VPS HUB (storage persistente).

### Fontes de Memória a Catalogar

| Fonte | Tipo | Conteúdo | Formato atual |
|-------|------|----------|---------------|
| **Serena MCP** | Session history | Lições aprendidas em sessões passadas | Sessions search em transcripts |
| **Basic Memory** | Arquivos JSON por projeto | Memórias estruturadas | `~/.basic-memory/memories/*.json` |
| **DEVORQ lessons** | Arquivos JSON | Lessons aprendidas via devorq | `.devorq/state/lessons/applied/*.json` |
| **Projetos individuais** | Docs分散adas | NOTES.md, LESSONS.md, TROUBLESHOOTING.md | Markdown |
| **dev-memory-laravel atual** | PostgreSQL | Memórias já catalogadas | Tabela `memories` |
| **VPS HUB** | Arquivos | Skills e lessons existentes | `/var/devorq/hub/lessons/` |

### Meta
- **100+ memórias** catalogadas na primeira leva
- **0 duplicatas** via deduplication por similaridade de título/descrição
- **100% pendente** para análise de conformidade
- **Métricas de recorrência** por problema/padrão

---

## 2. Modelo de Memória Standardizado

### 2.1 Campos Originais (dev-memory-laravel)
```php
// app/Models/Memory.php
[
    'id'           => 'uuid',
    'project_id'   => 'string|null',
    'title'        => 'string',
    'description'  => 'text',
    'type'         => 'enum: error|lesson|best_practice',
    'stack'        => 'string|null',        // ex: Laravel, PHP, Docker
    'scope'        => 'enum: project|global',
    'validation_status' => 'enum: pending|validated|rejected',
    'official_reference' => 'text|null',     // link para doc oficial
    'recurrence_count'  => 'integer',       // quantas vezes ocorreu
    'created_at'   => 'timestamp',
    'updated_at'   => 'timestamp',
    'deleted_at'   => 'timestamp|null',
]
```

### 2.2 Campos Novos (extensão para o programa)

```php
// Novos campos na tabela memories (migration)
[
    'source_system'      => 'enum: serena_mcp|basic_memory|devorq_lessons|file_docs|devmemory|null',
    'source_file'        => 'string|null',   // path do arquivo original
    'source_project'     => 'string|null',   // projeto de origem
    'original_id'        => 'string|null',   // ID original na fonte
    'recurrence_count'   => 'integer',       // vezes que este problema/padrão ocorreu
    'severity'           => 'enum: low|medium|high|critical|null',
    'external_reference' => 'url|null',      // link para transcrição/arquivo original
]
```

### 2.3 Enums Adicionais

```php
// app/Enums/MemorySource.php
enum MemorySource: string {
    case SERENA_MCP    = 'serena_mcp';
    case BASIC_MEMORY  = 'basic_memory';
    case DEVORQ_LESSONS = 'devorq_lessons';
    case FILE_DOCS     = 'file_docs';
    case DEVMEMORY     = 'devmemory';
    case VPS_HUB      = 'vps_hub';
    case MANUAL        = 'manual';
}

// app/Enums/Severity.php
enum Severity: string {
    case LOW      = 'low';
    case MEDIUM   = 'medium';
    case HIGH     = 'high';
    case CRITICAL = 'critical';
}
```

---

## 3. Pipeline de Captura

### FASE 1 — Discovery (Ferramentas)
Identificar TODAS as fontes antes de importar.

```
┌─────────────────────────────────────────────────────┐
│  DISCOVERY                                          │
│                                                     │
│  1. Serena MCP                                      │
│     → session_search em transcripts                 │
│     → buscar por "lesson", "learned", "error"       │
│                                                     │
│  2. Basic Memory                                    │
│     → ~/.basic-memory/memories/*.json               │
│     → projetos individuais                          │
│                                                     │
│  3. DEVORQ lessons                                  │
│     → /projects/*/.devorq/state/lessons/applied/  │
│     → /var/devorq/hub/lessons/.devorq/skills/      │
│                                                     │
│  4. File docs                                       │
│     → grep -r "LESSON\|TROUBLESHOOT\|ERROR"        │
│       em todos os projetos em /projects/             │
│                                                     │
│  5. dev-memory-laravel atual                        │
│     → SELECT * FROM memories (já catalogado)        │
└─────────────────────────────────────────────────────┘
```

### FASE 2 — Extração
Extrair cada fonte para JSON intermediário.

**Serena MCP:**
```bash
# Buscar sessões com lições
session_search limit=50 query="lesson OR learned OR error OR fix OR bug"

# Para cada sessão relevante:
# Extrair texto da sessão
# Parsear para formato Memory
```

**Basic Memory:**
```bash
# Encontrar arquivos
find ~/.basic-memory -name "*.json" 2>/dev/null
find /projects -path "*/.basic-memory/*.json" 2>/dev/null
```

**DEVORQ lessons:**
```bash
find /projects -path "*/.devorq/state/lessons/applied/*.json"
ssh -p 6985 root@187.108.197.199 \
  "find /var/devorq/hub/lessons -name '*.json' -path '*/applied/*'"
```

**File docs:**
```bash
# Buscar arquivos de lição
grep -rli "lesson\|troubleshoot\|problema\|erro\|fix\|bug" \
  /projects/*/docs/*.md \
  /projects/*/NOTES.md \
  /projects/*/LESSONS.md 2>/dev/null
```

### FASE 3 — Normalização
Converter todos os formatos para o schema padrão de Memory.

```php
// app/Services/MemoryNormalizer.php
class MemoryNormalizer
{
    public function normalize(array $raw, string $source): MemoryData
    {
        return new MemoryData([
            'title'        => $this->extractTitle($raw),
            'description'  => $this->extractDescription($raw),
            'type'        => $this->inferType($raw),       // error|lesson|best_practice
            'stack'       => $this->inferStack($raw),
            'scope'        => 'global',                     // primeira importação = global
            'source_system' => $source,
            'source_file'  => $raw['_source_file'] ?? null,
            'original_id'  => $raw['id'] ?? null,
            'severity'     => $this->inferSeverity($raw),
            'validation_status' => ValidationStatus::PENDING,
            'recurrence_count' => 1,
        ]);
    }
}
```

### FASE 4 — Deduplicação
Antes de importar, verificar se memória já existe.

```php
// Deduplication por similaridade
// 1. Title fuzzy match (Levenshtein distance < 3)
// 2. Description semantic match (se embedding disponível)
// 3. Stack + Type match

// Se duplicata encontrada:
// → Incrementar recurrence_count na memória existente
// → NÃO criar nova entrada
```

### FASE 5 — Importação
Importar para dev-memory-laravel (PostgreSQL) + sync para VPS HUB.

```
MemoryImportService
├── importFromSerenaMCP()
├── importFromBasicMemory()
├── importFromDevorqLessons()
├── importFromFileDocs()
├── importFromDevMemoryCurrent()
└── syncToVpsHub()           // SSH push para /var/devorq/hub/memories/
```

---

## 4. Validação e Conformidade

### 4.1 Processo de Validação

```
┌─────────────────────────────────────────────────────┐
│  VALIDAÇÃO (após importação massiva)                 │
│                                                     │
│  Phase 1: Triagem Rápida                           │
│  ├── Priorizar por recurrence_count (alta primero) │
│  ├── Priorizar por severity (critical > high)      │
│  └── Priorizar por age (mais antigas primeiro)    │
│                                                     │
│  Phase 2: Análise de Conformidade                  │
│  ├── Verificar título descritivo                   │
│  ├── Verificar descrição completa                  │
│  ├── Verificar stack correto                       │
│  ├── Verificar oficial_reference (link doc oficial) │
│  └── Verificar severity adequada                   │
│                                                     │
│  Phase 3: Decisão                                  │
│  ├── VALIDATED → conforma com docs oficiais       │
│  ├── REJECTED → não é memória válida              │
│  └── NEEDS_REVISION → incompleta, editar          │
└─────────────────────────────────────────────────────┘
```

### 4.2 Análise de Conformidade com Documentação Oficial

Para cada memória validada como ERROR:
```
1. Buscar documentação oficial da tecnologia
   → Context7 API (curl) para laravel, php, docker, etc.
2. Verificar se a "lição" está aligned com a doc
3. Se contradiz docs → marcar como "NEEDS_REVIEW"
4. Se aligned → VALIDATED
```

### 4.3 Interface de Validação (dev-memory-laravel)

- Filtro por `validation_status = PENDING`
- Ordenação por `recurrence_count DESC`
- Bulk actions: Validar, Rejeitar, Editar
- Indicador de conformidade (verde/amarelo/vermelho)

---

## 5. Métricas de Recorrência

### 5.1 Dashboard de Métricas

```php
// app/Services/MemoryMetricsService.php
public function getRecurrenceReport(): array
{
    return [
        // Top problemas mais recorrentes
        'top_recurring' => Memory::where('type', 'error')
            ->orderByDesc('recurrence_count')
            ->limit(10)
            ->get(['title', 'recurrence_count', 'stack']),

        // Problemas por stack
        'by_stack' => Memory::where('type', 'error')
            ->selectRaw('stack, SUM(recurrence_count) as total')
            ->groupBy('stack')
            ->orderByDesc('total')
            ->get(),

        // Problemas por severity
        'by_severity' => Memory::whereNotNull('severity')
            ->selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->get(),

        // Timeline de ocorrências
        'timeline' => Memory::where('type', 'error')
            ->selectRaw('DATE(created_at) as date, SUM(recurrence_count) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get(),
    ];
}
```

### 5.2 Identificação de Padrões

```
Problema recorrente:
  "Docker container não inicia em produção"
    → aparece em 7 projetos diferentes
    → 12 ocorrências totais
    → Stack: Docker
    → Severity: HIGH
    → Solução canônica: link para docs Docker

Padrão identificado:
  → Criar memória GLOBAL "Docker Production Gotchas"
  → Marcar todas ocorrências como "superseded"
```

---

## 6. Estrutura no VPS HUB

```
/var/devorq/hub/
├── skills/              ← 25 skills (ja existe)
├── lessons/             ← lessons DEVORQ (ja existe)
├── memories/            ← NOVO: todas as memórias catalogadas
│   ├── pending/         ← aguardando validação
│   ├── validated/       ← validadas
│   ├── rejected/        ← rejeitadas
│   └── superseded/      ← substituidas por versão global
├── metrics/             ← NOVO: relatórios de recorrência
│   ├── top_recurring.json
│   ├── by_stack.json
│   └── timeline.json
└── sync.lock            ← lock file
```

### Formato de cada memória no VPS (JSON)
```json
{
  "id": "uuid",
  "title": "Erro: Docker network mode host não funciona no Docker Desktop",
  "description": "Ao usar --network=host no Docker Compose...",
  "type": "error",
  "stack": "Docker",
  "scope": "global",
  "validation_status": "validated",
  "official_reference": "https://docs.docker.com/network/host/",
  "recurrence_count": 12,
  "severity": "high",
  "source_system": "basic_memory",
  "source_file": "/projects/gacpac-ti/.basic-memory/memories/docker-network.json",
  "original_id": "dm-123",
  "created_at": "2026-04-21T00:00:00Z",
  "validated_at": "2026-04-21T00:00:00Z",
  "validated_by": "Nando"
}
```

---

## 7. Plano de Execução

### Sprint 1: Infraestrutura
- [ ] Migration: adicionar campos novos na tabela `memories`
- [ ] Criar Enums: `MemorySource`, `Severity`
- [ ] Criar `MemoryNormalizer.php`
- [ ] Criar `MemoryMetricsService.php`
- [ ] Criar `VpsHubSyncService.php`
- [ ] Criar `MemoryImportCommand.php` (artisan command)

### Sprint 2: Extração
- [ ] Implementar `importFromBasicMemory()`
- [ ] Implementar `importFromDevorqLessons()`
- [ ] Implementar `importFromFileDocs()`
- [ ] Implementar `importFromSerenaMCP()`
- [ ] Testar extração de cada fonte

### Sprint 3: Deduplicação + Import
- [ ] Implementar deduplicação (Levenshtein)
- [ ] Pipeline completo de importação
- [ ] Sync para VPS HUB (`/var/devorq/hub/memories/`)
- [ ] 100+ memórias importadas

### Sprint 4: Interface de Validação
- [ ] Filtros PENDING + ordenação por recurrence
- [ ] Bulk actions (validar/rejeitar/editar)
- [ ] Dashboard de métricas
- [ ] Indicador de conformidade

### Sprint 5: Validação Massiva
- [ ] Análise de conformidade via Context7
- [ ] Validação de 100% das memórias
- [ ] Identificação de padrões
- [ ] Criação de memórias GLOBAIS para padrões

---

## 8. Gates (DEVORQ Flow)

| Gate | Critério | Status |
|------|----------|--------|
| GATE-1 | SPEC approved | pending |
| GATE-2 | Migration + Enums criados | pending |
| GATE-3 | MemoryNormalizer + VpsHubSyncService funcionando | pending |
| GATE-4 | Primeira extração (Basic Memory + DEVORQ lessons) | pending |
| GATE-5 | 100+ memórias importadas, deduplicadas | pending |
| GATE-6 | Interface de validação no ar | pending |
| GATE-7 | 100% das memórias validadas | pending |
