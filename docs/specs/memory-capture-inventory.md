# INVENTÁRIO: Fontes de Memória para Catalogação

**Projeto:** memory-capture-program  
**Data:** 2026-04-21  
**Status:** discovery completo  
**Autor:** Nando  

---

## Resumo do Levantamento

| Fonte | Tipo | Localização | Qtd Estimada | Formato |
|-------|------|-------------|--------------|---------|
| DEVORQ lessons | JSON | `.devorq/state/lessons/applied/` | ~1-5 | JSON |
| Troubleshooting docs | Markdown | `docs/TROUBLESHOOTING.md` | 6 items | Markdown |
| Handover docs | Markdown | `HANDOVER.md` | ~5 projetos | Markdown |
| Bug reports | Markdown | `Docs/COREOPS_BUG_REPORT.md` | 1 | Markdown |
| SPECs com gaps | Markdown | `docs/SPEC-*.md` | ~10 | Markdown |
| E2E audit reports | Markdown + JSON | `docs/report_e2e/` | ~6 | Markdown |
| Filament errors | Markdown | `SPEC-WIDGETS-ERRO.md` | 5 widgets | Markdown |
| Code lessons | Markdown | `skills/*/SKILL.md` | ~25 | SKILL.md |
| Basic Memory | — | não encontrado | 0 | — |
| Serena MCP sessions | sessions | transcript files | a verificar | — |

---

## 1. DEVORQ Lessons (fontes confirmadas)

**Localização:** `/projects/devorq_v3/.devorq/state/lessons/applied/`

### 1.1 lesson_20260421_183857_1758.json
- **Tópico:** Context7 REST API em vez de MCP CLI
- **Stack:** General / Context7
- **Tipo:** lesson (best practice)
- **Resumo:** MCP Context7 público usa API v1 desativada (404). API v2 funciona via curl REST direto.
- **Recorrência:** 1 (lição aprendida hoje)
- **Status atual:** applied

**Ação:** Importar como Memory com `source_system: devorq_lessons`

---

## 2. Troubleshooting Docs (fontes confirmadas)

### 2.1 /projects/devorq_v3/docs/TROUBLESHOOTING.md
| # | Problema | Solução | Stack |
|---|----------|---------|-------|
| T1 | "jq not found" warning | `apt install jq` | devorq |
| T2 | "Docker not found" warning | HUB feature opcional | devorq |
| T3 | "devorq: command not found" | adicionar ao PATH | devorq |
| T4 | "hooks.json not found" | `devorq init` | devorq |
| T5 | "Permission denied" em hooks | `chmod +x` | devorq |
| T6 | devorq init não funciona | verificar estrutura | devorq |

**Total:** 6 items → 6 memories (type: best_practice)

### 2.2 /projects/repo-vedovelli/docs/TROUBLESHOOTING.md
| # | Problema | Solução | Stack |
|---|----------|---------|-------|
| V1 | IDE reiniciando durante desenvolvimento | File watchers + inotify + Vite polling config | TypeScript/Vite |
| V2 | Hot reload lento | usePolling: false, ignored dirs | Vite |
| V3 | Limite inotify watches atingido | `sysctl fs.inotify.max_user_watches=524288` | Linux |

**Total:** 3 items → 3 memories (type: error)

---

## 3. Bug Reports (fontes confirmadas)

### 3.1 /projects/nandogravity/Docs/COREOPS_BUG_REPORT.md
- **Bug:** Coreops MCP checkpoint manual fica em loop
- **Sintomas:** `checkpoint_resolved` retorna true mas status continua pending
- **Causa provável:** Estado fantasma cacheado no MCP
- **Stack:** Coreops / TypeScript / MCP
- **Tipo:** error
- **Recorrência:** 1 (1 projeto)
- **Tentativas:** coreops_answer, coreops_next, coreops_start

**Ação:** Importar como Memory(type: error, stack: coreops)

---

## 4. SPECs com Gaps/Bugs (fontes confirmadas)

### 4.1 /projects/guest-list-pro/docs/SPEC-WIDGETS-ERRO.md
| Widget | Problema | Causa | Severity |
|--------|----------|-------|----------|
| CheckinFlowChart | Erro 500 no dashboard | session('selected_event_id') ausente no admin | high |
| PromoterPerformanceChart | Query falha | withCount + subqueries | medium |
| SectorOccupancyChart | Integração quebrada | Problema no model Sector | medium |
| SuspiciousCheckins | Deprecated API | Filament v4 incompatibilidade | medium |
| RequestsTimelineChart | Timeout | Queries complexas com datas | high |

**Total:** 5 memories (type: error, stack: laravel/filament)

### 4.2 /projects/eventos-control/docs/HANDOFF-EVENTOS-CONTROL.md
- **Bug:** SPEC-0053 — Modals não abrem nas telas de Index
- **Severity:** alta
- **Afeta:** Contracts Index, Receivables Index, Internacional Index
- **Stack:** Laravel / Livewire

**Ação:** Importar como Memory(type: error, stack: laravel/livewire, recurrence: 3)

---

## 5. Handover Docs (fontes confirmadas)

### 5.1 /projects/guest-list-pro/HANDOVER.md
Contém contexto valioso sobre:
- **Filament Policy bug:** TicketTypePolicy não estava registrada
- **Observer bug:** TicketSaleObserver usava `sendTo` em vez de `sendToDatabase`
- **Problema:** TicketTypeResource e AuditResource não registradas
- **Stack:** Laravel 12 + Filament v4 + Livewire v4

### 5.2 /projects/eventos-control/docs/HANDOFF-EVENTOS-CONTROL.md
Contém:
- **SPEC-0044:** Bug em notificação (NotificationBellTest corrigido)
- **SPEC-0053:** Bug crítico — modals não abrem
- **Enums criados:** 6 novos enums

### 5.3 /projects/nandogravity/Docs/TECHNICAL_DOC.md
Contexto do projeto:
- **Stack:** TypeScript + grammY + better-sqlite3
- **LLM:** Groq + OpenRouter fallback
- **Erro conhecido:** Nenhum crítico documentado

---

## 6. E2E Audit Reports (fontes confirmadas)

### 6.1 /projects/guest-list-pro/docs/report_e2e/AUDIT-FULL-2026-04-20.md
| Problema | Severity | Qtd Projetos | Stack |
|----------|----------|--------------|-------|
| 404 em TicketTypeResource | medium | 1 | Filament |
| 404 em AuditResource | medium | 1 | Filament |
| 404 em PromoterPermissionResource | medium | 1 | Filament |
| Links de navegação quebrados | low | 2 | Livewire |

**Total:** 4 memories (type: error, stack: filament)

### 6.2 /projects/guest-list-pro/docs/report_e2e/audit-errors.json
```json
{
  "consoleErrors": [],
  "navigationErrors": []
}
```
**Resultado:** Nenhum erro de console ou navegação — painel limpo.

---

## 7. VPS HUB Skills (fontes confirmadas)

**Localização:** `/var/devorq/hub/lessons/.devorq/skills/`

### 7.1 Skills DEVORQ (25 skills)
Cada skill contém seções "Pitfalls", "Common Errors", "Lessons Learned" que podem ser extraídas como memories.

| Skill | Tipo de learning | Pitfall count (estimado) |
|-------|-----------------|-------------------------|
| devorq-systematic-debugging | methodology | 3-5 |
| devorq-test-driven-development | methodology | 2-3 |
| devorq-agent-laravel | agent | 4-6 |
| devorq-agent-filament | agent | 3-5 |
| devorq-writing-plans | methodology | 2-3 |
| ... | ... | ... |

**Ação:** Extrair seções "Pitfalls" e "Common Errors" de cada skill como memories.

---

## 8. Basic Memory (NÃO ENCONTRADO)

**Busca em:** `~/.basic-memory/`, `/projects/*/.basic-memory/`

**Resultado:** Nenhum arquivo encontrado.

**Hipótese:** Basic Memory pode ter sido descontinuado ou usa formato diferente.

---

## 9. Serena MCP Sessions (A VERIFICAR)

**Status:** Não foi possível acessar transcripts diretamente.

**Ação futura:** Investigar se Serena MCP tem transcripts acessíveis via API ou arquivos locais.

---

## 10. File Docs com Lessons (fontes confirmadas)

### 10.1 /projects/guest-list-pro/.devorq/state/lessons-learned/2026-04-21-sqlite-vs-mysql-production.md
- **Tópico:** SQLite vs MySQL em Produção
- **Stack:** Laravel
- **Tipo:** lesson (error — problema real em produção)
- **Resumo:** Produção usava SQLite — gráficos não apareciam + login não persistia. Erro: `SQLSTATE[HY000]: General error: 1 no such function: HOUR`. 3 widgets afetados (SalesTimelineChart, CheckinFlowChart, AdminOverview). Solução: detectar driver e usar `strftime('%H')` para SQLite ou `HOUR()` para MySQL.
- **Recorrência:** 1 (1 projeto)
- **Severity:** high
- **Stack:** Laravel / MySQL / SQLite
- **Tags:** production, database, sqlite, mysql, performance
- **Commit do fix:** e032c75

**Ação:** Importar como Memory(type: error, stack: laravel/database)

### 10.2 /projects/eventos-control/.claude/skills/
- pest-testing/SKILL.md
- livewire-development/SKILL.md
- tailwindcss-development/SKILL.md

Cada SKILL.md pode conter lições extraíveis.

---

## Resumo de Quantidades

| Tipo | Estimativa |
|------|-----------|
| Memories tipo ERROR | ~20 |
| Memories tipo LESSON | ~10 |
| Memories tipo BEST_PRACTICE | ~15 |
| **Total estimado (primeira leva)** | **~45** |
| Com deduplicação | ~35-40 |

---

## Priorização para Importação

### Alta Prioridade (importar primeiro)
1. DEVORQ lessons (1 lesson)
2. Troubleshooting docs (9 items: T1-T6 + V1-V3)
3. Bug reports (2 items: coreops + SPEC-0053)
4. E2E audit errors (4 items: 404s)

### Média Prioridade (importar segundo)
5. SPEC-WIDGETS-ERRO (5 widgets com erro)
6. Handover docs (padrões identificados)
7. Lessons-learned docs

### Baixa Prioridade (importar terceiro)
8. VPS HUB skills (extrair pitfalls)
9. .claude/skills docs

---

## Formato de Importação (JSON intermediário)

```json
{
  "title": "Coreops MCP checkpoint manual fica em loop",
  "description": "checkpoint_resolved retorna true mas status continua pending. Causaprovável: estado fantasma cacheado no MCP.",
  "type": "error",
  "stack": "coreops",
  "scope": "global",
  "source_system": "coreops_bug_report",
  "source_file": "/projects/nandogravity/Docs/COREOPS_BUG_REPORT.md",
  "source_project": "nandogravity",
  "severity": "high",
  "recurrence_count": 1,
  "validation_status": "pending",
  "official_reference": null
}
```

---

## Ações Imediatas

- [ ] Criar migration com campos novos (`source_system`, `severity`, etc.)
- [ ] Criar Enums: `MemorySource`, `Severity`
- [ ] Criar `MemoryNormalizer.php`
- [ ] Implementar extração: DEVORQ lessons (1 item)
- [ ] Implementar extração: Troubleshooting docs (9 items)
- [ ] Implementar extração: Bug reports (2 items)
- [ ] Ver arquivo: `2026-04-21-sqlite-vs-mysql-production.md`
- [ ] Investigar Serena MCP sessions
