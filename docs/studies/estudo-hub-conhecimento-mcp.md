# Estudo Aprofundado — Plataforma Central de Memória e Conhecimento (Hub MCP na VPS)

**Data:** 2026-07-10
**Base:** requisição do Nando (visão da plataforma) + inventário do dev-memory-laravel + specs existentes (`memory-capture-program.md`, `vps-hub-integration.md`) + verificação do ecossistema MCP/Laravel em julho/2026
**Status:** estudo — insumo para decisão de arquitetura (pré GATE-1)

---

## 1. Sumário Executivo

A visão é **viável e o timing é excelente** — o ecossistema amadureceu exatamente na direção que a requisição precisa:

- O protocolo MCP consolidou o **transporte remoto** (Streamable HTTP) com autorização OAuth 2.1; a revisão 2026-07-28 traz núcleo stateless que roda atrás de load balancer comum.
- Existe **pacote oficial `laravel/mcp`** com Streamable HTTP e autenticação via Sanctum/Passport — elimina o servidor JSON-RPC artesanal atual.
- O Laravel 13 tem **busca vetorial nativa no Eloquent** (`whereVectorSimilarTo`) sobre pgvector — a "recuperação semântica" da requisição é feature de framework, não projeto de infraestrutura.

O dev-memory-laravel já cobre **~40% da fundação** (modelo de dados, CRUD, validação, 5 tools MCP, importadores, UI). Porém, realizar a visão exige **uma inversão arquitetural** em relação à spec `vps-hub-integration.md` atual, e a requisição contém **dois pontos que o MCP não consegue garantir sozinho** (captura transparente e consulta prévia obrigatória) — ambos têm solução, mas ela mora no lado do cliente, não no servidor. Este estudo detalha isso.

---

## 2. Decomposição da Requisição — 7 Pilares

| # | Pilar | Essência |
|---|-------|----------|
| P1 | **Hub central na VPS como serviço MCP** | Uma base única, independente de projeto, acessível de qualquer máquina/IDE/agente |
| P2 | **Captura automática de eventos** | Bugs, decisões, reviews, fixes → classificados e enviados ao hub sem fricção |
| P3 | **Estruturação + recuperação semântica** | Conhecimento indexado por significado, não só texto |
| P4 | **Consulta preventiva no planejamento** | Antes de implementar: lições, riscos, padrões e decisões anteriores chegam ao agente |
| P5 | **Validação e promoção (Context7)** | Só conhecimento validado vira camada permanente; distinguir workaround de prática canônica |
| P6 | **Repositório de Skills versionado** | Competências operacionais idênticas para qualquer agente conectado |
| P7 | **Hub de referências técnicas** | Catálogo de repos próprios, templates, libs aprovadas, ferramentas (ex.: Devorq) |

A frase-síntese da requisição — *"sistema operacional de conhecimento"* — descreve um **ciclo fechado**: capturar (P2) → estruturar (P3) → validar (P5) → servir preventivamente (P4), com P1 como substrato e P6/P7 como acervos especiais.

---

## 3. Onde Você Já Está (inventário honesto)

### Já existe e serve à visão
- **Modelo de dados maduro**: `memories` com type/scope/validation_status/severity/source_system/recurrence_count — já modela o ciclo *pendente → validado → promovido a global* (P5 parcial).
- **5 tools MCP funcionais** (`memory_list/search/get/create/stats`) — a semente de P1, hoje via stdio local.
- **Programa de captura** (`memory:import` com 6 parsers + `MemoryNormalizer` com inferência de type/severity/stack) — a semente de P2, hoje batch/retroativo, não contínuo.
- **Métricas de recorrência** (`MemoryMetricsService`, sugestão de duplicatas) — insumo direto para "problemas recorrentes" de P4.
- **UI de curadoria** (Livewire, design Neo) — o trilho humano de validação de P5.
- **25 skills DEVORQ já no VPS** (`/var/devorq/hub/skills/`) — matéria-prima de P6.

### Existe mas aponta na direção errada
- **`VpsHubSyncService`**: SSH com `root@187.108.197.199` hardcoded, `shell_exec`, arquivos JSON como storage. É o inverso de "serviço compartilhado": cada máquina precisa de chave SSH root, não há autenticação por cliente, não há API, não escala para N ambientes. **Este componente é descartável na arquitetura-alvo.**
- **Spec `vps-hub-integration.md`**: trata o Laravel como *bridge* local que lê arquivos do VPS via SSH. A requisição pede o oposto: o serviço **mora** na VPS e os ambientes são clientes.

### Não existe ainda
- Transporte MCP remoto (HTTP) e autenticação por cliente.
- Embeddings/busca semântica (a busca atual é `LIKE`).
- Captura contínua durante sessões (hooks/gatilhos).
- Fluxo Context7 automatizado (a spec §4.2 o descreve, mas nada implementado).
- Skills e referências expostas via MCP (P6/P7 — zero código; `devorq_lessons` é uma migration órfã sem model).

---

## 4. Análise de Viabilidade — Pilar a Pilar

### P1 — Hub central na VPS ✅ viável, exige inversão arquitetural

**Decisão central do estudo**: o dev-memory-laravel deve **deixar de ser um frontend local com ponte SSH e se tornar o próprio hub, hospedado na VPS**.

- Migrar o servidor MCP artesanal (stdio, JSON-RPC manual em `MemoryMcpServer.php`) para o pacote oficial **`laravel/mcp`** com **Streamable HTTP** — cada ambiente conecta com uma URL + token, sem SSH, sem processo local.
- Autenticação: **Sanctum** (token por máquina/agente) é suficiente para uso pessoal; OAuth 2.1 completo (exigido pela spec 2026 para clientes públicos) fica como evolução se o hub um dia atender terceiros.
- Storage: **PostgreSQL no hub** como fonte de verdade (o projeto já tem docker-compose com Postgres 16 + Redis). Os arquivos `/var/devorq/hub/memories/*.json` viram **export/backup**, não storage primário — arquivos JSON via SSH não suportam busca semântica, transação nem concorrência de N clientes.
- O stdio continua disponível para desenvolvimento local (o `laravel/mcp` suporta ambos os transportes).

### P2 — Captura automática ⚠️ viável, mas a "transparência" mora no cliente

**Reality check**: um servidor MCP é *passivo* — ele responde a chamadas, não observa a sessão. Nenhum design de servidor torna a captura "transparente" por si só. A captura automática se constrói no **lado do cliente**, com o hub oferecendo ingestão fácil:

| Mecanismo | Onde roda | Esforço | Cobertura |
|-----------|-----------|---------|-----------|
| **Hooks do Claude Code** (`PostToolUse`, `Stop`, `SessionEnd`) postando para a API do hub | cada máquina | baixo | alta no seu fluxo principal |
| **Instruções padrão** (CLAUDE.md global / skills) orientando o agente a chamar `memory_create` ao resolver bug/decidir arquitetura | qualquer agente MCP | mínimo | média (depende de adesão do modelo) |
| **Skill dedicada** (`/remember`, `/lesson`) para captura deliberada | qualquer cliente | mínimo | garantida, mas manual |
| **Importadores batch** (já existem) para varrer docs/git retroativamente | hub ou local | pronto | retroativa |

Recomendação: **camadas combinadas** — hooks para o grosso automático + instrução global como rede + skill manual para o que importa muito. O precedente já existe no seu ambiente: o plugin claude-mem injeta contexto via SessionStart hook — o mesmo padrão, invertido, serve para capturar.

A classificação automática ("classificados e encaminhados") já tem base: o `MemoryNormalizer` infere type/severity/stack por heurística. Evolução natural: um **job de classificação com LLM** na fila do hub para eventos brutos (Haiku resolve barato).

### P3 — Recuperação semântica ✅ viável, caminho pavimentado

- **pgvector** no Postgres do hub + coluna `embedding` em `memories`.
- Pipeline: observer/job na fila gera embedding em create/update (provedor a decidir: OpenAI, Voyage, ou local via Ollama).
- Busca **híbrida**: `whereVectorSimilarTo` (nativo no Laravel 13) para semântica + keyword para termos exatos; a tool `memory_search` passa a combinar os dois rankings.
- **Bônus direto**: a deduplicação por Levenshtein prevista na spec de captura é fraca; similaridade de embeddings > threshold → incrementa `recurrence_count` em vez de criar duplicata. Resolve dedup e métrica de recorrência com o mesmo mecanismo.

### P4 — Consulta preventiva no planejamento ⚠️ viável, mas não "forçável" pelo servidor

Mesmo reality check de P2: o hub não pode *obrigar* um agente a consultá-lo antes de planejar. O que funciona:

1. **Skills de planejamento padronizadas** (brainstorm/spec/feature) cujo primeiro passo é chamar uma tool agregadora do hub — você já opera assim com DEVORQ (GATE-0.5, project-foundation); é encaixar a consulta no gate.
2. **Tool desenhada para o momento certo**: uma única tool `hub_briefing(context: stack, tipo_de_tarefa, descrição)` que retorna o pacote completo — lições relacionadas, riscos, padrões aprovados, decisões anteriores — em uma chamada. Tool descriptions bem escritas fazem agentes usarem espontaneamente.
3. **MCP Prompts**: o protocolo suporta prompts expostos pelo servidor (ex.: `plan-with-memory`) que o usuário invoca no cliente.
4. **SessionStart hook** injetando um resumo do hub relevante ao projeto no início de cada sessão.

O item 2 é o mais importante e é **código novo no hub** (agregador de briefing). Os itens 1, 3 e 4 são configuração/convenção nos clientes.

### P5 — Validação via Context7 ⚠️ viável com escopo bem definido

O Context7 valida contra **documentação oficial de bibliotecas/frameworks** — cobre bem memórias do tipo "erro/lição sobre Laravel/Docker/Livewire". Mas **não valida** decisões arquiteturais pessoais, convenções próprias nem conhecimento de domínio — não existe "doc oficial" para isso.

Recomendação: **dois trilhos de validação**:
- **Trilho automático (Context7)**: worker na fila que, para memórias com stack identificada, consulta a doc oficial e marca *conforme / contradiz / não-verificável*. Alimenta o "indicador de conformidade" já previsto na spec (§4.3).
- **Trilho humano (UI)**: bulk actions de validação já planejadas; decisões próprias só passam por aqui.

A distinção da requisição (*provisório / workaround / recomendado / canônico / consolidado*) pede um refinamento do modelo: hoje `validation_status` tem 3 estados. Sugestão: adicionar um campo `maturity` (ex.: `workaround | provisional | recommended | canonical`) ortogonal ao status de validação — e criar o estado `superseded` que o código de sync já referencia mas o enum não tem (bug conhecido).

### P6 — Repositório de Skills ✅ viável, git como fonte de verdade

Skills são arquivos versionáveis — **git é o mecanismo natural** (histórico, revisão, rollback), não tabela de banco:

- Repositório git de skills (as 25 do VPS migram para lá).
- O hub faz `git pull` (webhook ou cron) e **expõe via MCP**: resources (`skill://laravel/...`) + tool `skill_search(stack, tag)`.
- Qualquer agente conectado lê exatamente a mesma versão — o requisito de consistência entre agentes vem de graça.

### P7 — Hub de referências ✅ trivial tecnicamente

Tabela `references` (nome, tipo, url, stack, tags, notas) + CRUD na UI + tools `reference_list/search`. O valor está na **curadoria**, não na técnica. Vale integrar ao `hub_briefing` de P4 (ex.: "para Laravel+Livewire, os templates aprovados são X e Y").

---

## 5. Tensões e Riscos da Requisição

| Risco | Impacto | Mitigação |
|-------|---------|-----------|
| **Qualidade da captura automática** — captura demais vira ruído; recuperação semântica sobre lixo devolve lixo com confiança | alto | Gate de curadoria já existe (pending → validated); classificador LLM filtra antes de gravar; métricas de *uso* das memórias (quais briefings ajudaram) para poda |
| **Ponto único de falha** — VPS fora do ar = todos os ambientes sem memória | médio | Degradação graciosa (a spec já prevê: hub offline → `[]` com warning, nunca bloqueia); cache local com TTL nos clientes; backups automatizados do Postgres |
| **Segurança** — todo o conhecimento técnico (incluindo detalhes de projetos de clientes) num serviço exposto por HTTP | alto | TLS obrigatório, tokens Sanctum por ambiente com revogação, rate limit; **eliminar o SSH root hardcoded atual**; avaliar campo `confidential` para memórias que não saem do escopo do projeto |
| **Latência no planejamento** — consulta preventiva síncrona não pode custar minutos | médio | Tool `hub_briefing` agregada (1 chamada, não 5); índices pgvector (HNSW); Redis para briefings repetidos |
| **Escopo global vs. projeto** — lição de um projeto pode ser antipadrão em outro | médio | O modelo scope/promotion já trata; a promoção a global continua sendo decisão validada, nunca automática |
| **Heterogeneidade de clientes** — "mesmo padrão em qualquer IDE" depende do que cada cliente MCP suporta (hooks, prompts, resources) | médio | Núcleo em tools (suporte universal); hooks/prompts como camadas progressivas por cliente |
| **Custo/manutenção de embeddings** — reindexação ao trocar de provedor/modelo | baixo | Volume pessoal é pequeno (centenas→milhares); job de reindex em fila resolve |

---

## 6. Arquitetura-Alvo Recomendada

```
┌────────────────────────── VPS (srv163217) ──────────────────────────┐
│                                                                     │
│  dev-memory-laravel  (ESTE projeto, promovido a hub)                │
│  ├── MCP Server (laravel/mcp, Streamable HTTP + Sanctum)            │
│  │   ├── tools: memory_* (5 atuais) + hub_briefing +                │
│  │   │          skill_search + reference_search                     │
│  │   ├── prompts: plan-with-memory, capture-lesson                  │
│  │   └── resources: skill://..., memories://list                    │
│  ├── API REST /api/ingest  (captura via hooks; token por máquina)   │
│  ├── UI Livewire (curadoria, validação, métricas)  [já existe]      │
│  ├── Fila (Redis): embeddings, classificação LLM, validação Context7│
│  ├── PostgreSQL 16 + pgvector  (fonte de verdade)                   │
│  └── Skills: git repo → pull → expostas via MCP     [git = verdade] │
│                                                                     │
└───────────────▲──────────────────▲──────────────────▲───────────────┘
        HTTPS + token       HTTPS + token       HTTPS + token
                │                  │                  │
     ┌──────────┴─────┐  ┌─────────┴──────┐  ┌────────┴─────────┐
     │ Máquina WSL     │  │ Outra máquina  │  │ Qualquer agente  │
     │ Claude Code     │  │ IDE + agente   │  │ MCP (Codex etc.) │
     │ + hooks captura │  │ + .mcp.json    │  │                  │
     └────────────────┘  └────────────────┘  └──────────────────┘
```

O que **muda** em relação a hoje: o Laravel sai da máquina local e vira o serviço na VPS; SSH desaparece do caminho crítico; arquivos JSON viram export. O que **permanece**: modelo de dados, services, UI, importadores — o investimento atual é aproveitado quase integralmente.

---

## 7. Roadmap Proposto (fases com gates DEVORQ)

| Fase | Entrega | Depende de | Esforço relativo |
|------|---------|-----------|------------------|
| **F0 — Higiene** | Credenciais SSH fora do código (config/.env); corrigir enum `superseded`; decidir destino da tabela órfã `devorq_lessons` | — | pequeno |
| **F1 — Inversão** | Deploy do dev-memory-laravel na VPS (docker-compose já pronto); migrar MCP para `laravel/mcp` com Streamable HTTP + Sanctum; `.mcp.json` dos projetos apontando para a URL | F0 | médio |
| **F2 — Semântica** | pgvector + pipeline de embeddings; `memory_search` híbrida; dedup semântica substituindo Levenshtein | F1 | médio |
| **F3 — Captura contínua** | Endpoint `/api/ingest`; hooks Claude Code (PostToolUse/Stop); classificador LLM na fila; instrução no CLAUDE.md global | F1 | médio |
| **F4 — Prevenção** | Tool `hub_briefing`; MCP prompts; encaixe nos gates DEVORQ (GATE-0.5 consulta o hub); SessionStart hook | F2 | médio |
| **F5 — Validação** | Worker Context7 na fila; campo `maturity`; UI de conformidade + bulk actions (spec §4 já desenha) | F2 | médio |
| **F6 — Acervos** | Skills git-backed expostas via MCP; tabela + tools de referências | F1 | pequeno |
| **F7 — Operação** | Backups, TLS/renovação, observabilidade, métricas de uso das memórias (feedback loop de utilidade) | F1+ | contínuo |

F2 e F3 são paralelizáveis após F1. O ciclo completo da visão fecha em F4+F5.

---

## 8. Decisões em Aberto (para o dono da plataforma)

1. **Confirmar a inversão** — o dev-memory-laravel vira O hub na VPS (recomendação deste estudo), o que **supersede a spec `vps-hub-integration.md`** (modelo bridge/SSH). As duas direções são incompatíveis; seguir com ambas gera retrabalho.
2. **Provedor de embeddings** — API externa (OpenAI/Voyage: melhor qualidade, custo por token) vs. local na VPS (Ollama: grátis, exige RAM/CPU da VPS). Depende do porte da VPS.
3. **Autenticação** — Sanctum simples por token (recomendado para uso pessoal) vs. OAuth 2.1 completo (só se o hub for atender terceiros).
4. **`devorq_lessons`** — consolidar como `memories` com `source_system=devorq_lessons` (recomendado: um modelo único simplifica busca/validação) vs. manter tabela própria.
5. **Agressividade da captura** — hooks capturando tudo com filtro posterior vs. captura seletiva orientada por instrução. Sugestão: começar seletivo, abrir o funil conforme a curadoria der conta.

---

## 9. Fontes

- [Laravel MCP — pacote oficial](https://laravel.com/ai/mcp) e [laravel/mcp no Packagist](https://packagist.org/packages/laravel/mcp)
- [Laravel AI Agents Now Support MCP Servers](https://laravel.com/blog/laravel-ai-agents-now-support-mcp-servers)
- [MCP Specification 2026-07-28 Release Candidate](https://blog.modelcontextprotocol.io/posts/2026-07-28-release-candidate/)
- [Authorization — Model Context Protocol](https://modelcontextprotocol.io/specification/draft/basic/authorization)
- [OAuth 2.1 for Remote MCP Servers (2026)](https://mcp.directory/blog/oauth-21-for-remote-mcp-servers-streamable-http-explained-2026)
- [Laravel 13 — Search (vector search nativo)](https://laravel.com/docs/13.x/search)
- [Native Vector Search in Eloquent](https://pradeepbhandari.com/blog/laravel-native-vector-search-eloquent-pgvector)
- [pgvector para Laravel Scout](https://benbjurstrom.com/pgvector-for-laravel-scout)
- Specs internas: `docs/specs/memory-capture-program.md`, `docs/specs/vps-hub-integration.md`
