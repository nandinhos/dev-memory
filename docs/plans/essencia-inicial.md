# Plano — Completar a Essência Inicial do dev-memory

**Data:** 2026-07-15
**Objetivo:** tornar o dev-memory um hub de conhecimento vivo, logável, administrável e conectável via MCP — sua essência inicial completa. Sem Filament, sem `nando/ai-motor`; evolui a UI Livewire + design system "neo" existente.

---

## 1. Estado atual (verificado)

**Backend de curadoria: completo** (P1–P6 + F6, 124 testes verdes). Captura imutável → sanitização determinística → curadoria MiniMax (structured output + reparo) → política de promoção → recorrência composta → validação documental Context7 → agrupamento de skills → compilação com rastreabilidade de fonte → publicação em git.

**Gap central: tudo isso é CLI-only.**
- UI Livewire/neo cobre **só memórias** (Dashboard, List, Detail, Form). 5 rotas públicas, **sem autenticação**.
- **Nenhuma UI** para Captures, Skills, Skill Groups, Curation Executions nem status de validação documental. Grupos são aprovados via `tinker`; skills são publicadas via CLI.
- MCP: servidor artesanal (`MemoryMcpServer`, stdio, 5 tools de memória). **Sem `laravel/mcp`, sem Sanctum, sem acesso remoto.**
- Auth: nenhuma. 1 usuário semeado no banco, sem login nem proteção de rota.

---

## 2. Blocos de trabalho

### Bloco A — Autenticação (fundação)
*É o "entro via login"; desbloqueia a administração e os tokens de MCP.*
- Login por sessão com tela própria no design system neo (sem Breeze/Filament).
- Middleware `auth` em todas as rotas web; logout.
- Admin único semeado, sem registro aberto (hub pessoal).
- Gestão de tokens de API (Sanctum) para os projetos consumidores — base do Bloco C.

**Esforço:** pequeno-médio. **Testes:** proteção de rota, fluxo de login/logout.

### Bloco B — Painel de administração do pipeline (coração do "sistema vivo")
*Hoje o pipeline é caixa-preta dirigida por artisan; aqui vira administrável pela UI.*
- **Inbox de Captures:** entradas, status (sanitized/curated/discarded/failed), resultado de dedup, bruto vs. sanitizado.
- **Validação documental nas memórias:** badge (confirmed/contradicted/partially/inconclusive) + fontes oficiais — o dado já existe, falta exibir.
- **Revisão de Skill Groups:** aprovar/rejeitar na UI (hoje é tinker).
- **Skills:** listar, revisar draft, aprovar, publicar (hoje é CLI).
- **Log de auditoria** (`curation_executions`): modelo, prompt_version, tentativas, tokens.
- **Ações em lote** de validação nas memórias pendentes.

**Esforço:** médio-grande (maior gap de valor). **Testes:** componentes Livewire + transições de estado.

### Bloco C — F1: MCP remoto (o "funciona via MCP em outros projetos")
*Transforma a ferramenta local em hub compartilhado.*
- Migrar `MemoryMcpServer` artesanal → pacote oficial `laravel/mcp` com Streamable HTTP + Sanctum.
- Manter stdio para uso local.
- Tokens por projeto/máquina (do Bloco A), com revogação.

**Esforço:** médio. **Testes:** tools via HTTP autenticado.

### Bloco D — Consulta preventiva + ingestão (fecha o ciclo)
*"Entrega de dados para planejamento" + porta de entrada de captura externa.*
- Tool `hub_briefing(contexto)`: pacote agregado — lições relacionadas, riscos, recorrências, padrões aprovados — numa chamada.
- Entrada de ingestão (tool MCP / endpoint) que alimenta o `CaptureService` a partir de interfaces externas (hooks, agentes).

**Esforço:** médio. **Testes:** agregação do briefing, ingestão idempotente ponta-a-ponta.

---

## 3. Sequência e dependências

```
A (auth + tokens)  →  B (admin UI)  ∥  C (MCP remoto)  →  D (briefing + ingestão)
```
A é fundação. B e C são paralelizáveis após A. D depende de C (briefing é tool MCP; ingestão alimenta o pipeline).

---

## 4. Fora do escopo da "essência inicial" (depois)

- **Inversão física do hub para a VPS** (deploy) — a arquitetura já suporta; é operação, não código.
- **Embeddings/pgvector reais** — o TF-cosseno atual serve; troca quando o hub for para a VPS com Postgres.
- **Campo `maturity`** e publicação das skills no repositório git definitivo (GitHub).

---

## 5. Disciplina de qualidade

Cada bloco mantém os 124 testes verdes, roda Pint e é verificado ponta-a-ponta. Auth e tokens com testes de proteção de rota. Commits granulares no padrão DEVORQ.
