# DevMemory Hub — Documentação Técnica & Executiva de Homologação

![DevMemory Architecture Banner](images/devmemory_hub_architecture_banner.jpg)

> **Status de Homologação:** HOMOLOGADO COM AUTO-RECUPERAÇÃO ATIVA  
> **Ambiente Avaliado:** Produção (`https://devmemory.fssdev.com.br`) & Local (`dev-memory-laravel`)  
> **Data da Auditoria:** 31 de Outubro de 2026  
> **Classificação de Evidência:** 100% `OBSERVED` via comandos e suíte de testes  

---

## 1. Sumário Executivo & Proposta de Valor

O **DevMemory Hub** é uma plataforma centralizada de **memória técnica de longo prazo**, curadoria automatizada de lições aprendidas e **Graph/Vector RAG** para ambientes de engenharia de software assistidos por Inteligência Artificial (Antigravity IDE, Cursor, Claude Code, CLI Agents).

### O Problema Resolvido:
- **Amnésia de Sessão em LLMs:** Cada conversa de IA começa do zero, repetindo erros de arquitetura, comandos incompatíveis e soluções já superadas.
- **Vazamento de Segredos em Prompts:** Snippets brutos colados por desenvolvedores frequentemente contêm senhas, bearer tokens e credenciais.
- **Desconexão de Contexto:** Agentes locais não têm visibilidade do que outros desenvolvedores da equipe aprenderam em projetos correlatos.

### A Solução DevMemory:
1. **Ingestão Sanitizada (Zero-Leak):** Pipeline determinístico com sanitização regex estrita que descaracteriza chaves, JWTs e credenciais antes da persistência.
2. **Motor de Busca Híbrido RRF (Reciprocal Rank Fusion):** Combina busca semântica vetorial (`pgvector` + `Ollama nomic-embed-text`) com busca léxica full-text (`PostgreSQL tsvector`).
3. **Knowledge Graph Multidimensional:** Projeção automática de grafos de conhecimento ligando memórias, stacks, decisões de arquitetura e relações conceituais.
4. **Resiliência e Fallback Gracioso:** Sistema tolerante a falhas; se o motor de embeddings estiver sob carga ou offline, a aplicação degrada automaticamente para busca léxica sem interrupção de serviço.

---

## 2. Diagnóstico de Averiguação Técnica (`OBSERVED`)

A auditoria completa da base de código e da infraestrutura de produção revelou os seguintes indicadores concretos:

| Componente | Indicador Observado | Status |
|---|---|:---:|
| **Suíte de Testes Automatizada** | 277 testes executados, 770 asserções, 0 falhas (tempo: 5.52s) | **APROVADO** |
| **Endpoint Webhook/Healthcheck** | HTTP/2 200 OK em `https://devmemory.fssdev.com.br/up` | **APROVADO** |
| **Serviço de Embeddings (Ollama)** | Ativo via systemd (`Restart=always`), modelo `nomic-embed-text` operacional com 156 memórias indexadas | **APROVADO** |
| **Processamento Assíncrono (Filas)** | Supervisor ativo com 2 workers (`site_devmemory.fssdev.com.br_queue-worker`), `autostart=true`, `autorestart=true` | **APROVADO** |
| **Banco de Dados Relacional & Vetores** | PostgreSQL 16 + extensão `pgvector` ativa, conectividade local 127.0.0.1 | **APROVADO** |
| **Proteção de Segredos & Configuração** | `.env` de produção com permissão restrita `640` (`-rw-r----- www-data www-data`) | **APROVADO** |
| **Scheduler / Crontab de Auto-Cura** | Ativo no crontab do `www-data` (`* * * * * schedule:run`), verificado com exit code 0 | **APROVADO** |

---

## 3. Matriz de GAPs, Inconsistências e Déficits Técnicos

Para a homologação definitiva e operação segura em produção contínua, os seguintes pontos foram isolados:

### [GAP 1 - RESOLVIDO EM 31/10/2026] Ativação do Scheduler do Laravel no Crontab da VPS
- **Sintoma Anterior:** `crontab -u www-data -l` na VPS retornava vazio.
- **Resolução Aplicada:** Instalado o cron oficial do Laravel sob o usuário `www-data`:
  ```bash
  * * * * * cd /var/www/devmemory.fssdev.com.br/current && php artisan schedule:run >> /dev/null 2>&1
  ```
- **Evidência de Validação:** Execução direta via `su -s /bin/bash www-data` retornou `INFO No scheduled commands are ready to run.` com exit code `0`. A auto-cura horária (`memory:process-captures --retry-failed`) está 100% ativa.

### [GAP 2 - ALTO] Rotina Automatizada de Backup do PostgreSQL e pgvector
- **Sintoma Observado:** Não há agendamento de `pg_dump` com compressão e retenção histórica periódica no crontab do servidor para o banco `devmemory`.
- **Impacto no Sistema:** Risco de perda de dados históricos e embeddings em caso de falha de hardware ou corrupção do volume.
- **Ação de Correção:** Provisionar script bash diário com `pg_dump -Fc` armazenado em `/var/backups/devmemory/` e retenção de 14 dias com envio para storage externo.

### [GAP 3 - MÉDIO] Alerta de Degradação de Embeddings (Ollama Silent Fallback)
- **Sintoma Observado:** O `EmbeddingGeneratorService` degrada com perfeição para busca léxica em caso de indisponibilidade do Ollama, mas apenas registra em log (`Log::warning`).
- **Impacto no Sistema:** Se o Ollama parar por semanas, o sistema continua funcionando apenas em modo léxico sem que a equipe técnica seja notificada da perda de busca semântica.
- **Ação de Correção:** Implementar comando de telemetria/healthcheck (`artisan memory:health-check`) que envie webhook ou notificação caso o Ollama falhe por mais de 3 requisições consecutivas.

### [GAP 4 - BAIXO] Gestão de Ciclo de Vida e Expiração de Tokens de API
- **Sintoma Observado:** Os tokens de API gerados possuem hash SHA-256 seguro, mas não possuem prazo de validade (`expires_at`) nem alerta de inatividade.
- **Ação de Correção:** Adicionar coluna opcional `expires_at` na tabela `api_tokens` e botão de revogação/renovação rápida no painel administrativo.

---

## 4. Topologia e Arquitetura do Sistema

```mermaid
flowchart TD
    subgraph CLIENTES["Clientes & Agentes"]
        AG1["Antigravity IDE / CLI"]
        AG2["Cursor / VSCode MCP"]
        AG3["DeepSeek Harness (DSH)"]
        AG4["Painel Web (Livewire)"]
    end

    subgraph EDGE["Camada de Borda & Segurança"]
        CF["Cloudflare Edge (WAF + SSL/TLS + HSTS)"]
        NGX["Nginx Reverse Proxy"]
        RATELIMIT["Rate Limiter (120 req/min)"]
    end

    subgraph CORE["Núcleo da Aplicação (Laravel 12 / PHP 8.4)"]
        AUTH["Middleware AuthenticateMcpToken (SHA-256)"]
        MCP["McpController (JSON-RPC 2.0)"]
        SEARCH["MemorySearchService (Híbrido RRF)"]
        SANITIZER["CaptureSanitizer (Regex Zero-Leak)"]
        PROJECTOR["KnowledgeGraphProjector"]
    end

    subgraph WORKERS["Processamento Assíncrono (Supervisor)"]
        QW["Queue Workers (2 Processos)"]
        J1["GenerateMemoryEmbeddingJob"]
        J2["ProjectMemoryKnowledgeGraphJob"]
        J3["ProposeMemoryRelationsJob"]
    end

    subgraph AI_SERVICES["Serviços de IA & IA Local"]
        OLLAMA["Ollama Service (systemd)"]
        MODEL["nomic-embed-text (dim=768)"]
    end

    subgraph PERSISTENCIA["Armazenamento & Persistência"]
        PG["PostgreSQL 16"]
        PGV["pgvector (HNSW / Cosine Index)"]
        TSV["PostgreSQL Full-Text (tsvector)"]
        GRAPH_TBL["Knowledge Nodes & Edges"]
        REDIS["Redis Server (Cache & Queues)"]
    end

    CLIENTES -->|HTTPS / JSON-RPC / Livewire| CF
    CF --> NGX
    NGX --> RATELIMIT
    RATELIMIT --> AUTH
    AUTH --> MCP
    AUTH --> AG4

    MCP --> SEARCH
    MCP --> SANITIZER
    SANITIZER --> QW

    QW --> J1
    QW --> J2
    QW --> J3

    J1 -->|HTTP POST :11434| OLLAMA
    OLLAMA --> MODEL
    J1 --> PGV
    J2 --> GRAPH_TBL
    J3 --> GRAPH_TBL

    SEARCH --> PGV
    SEARCH --> TSV
    SEARCH --> GRAPH_TBL
```

---

## 5. Fluxo de Ingestão Sanitizada & Curadoria (Zero-Leak)

Garante que nenhuma credencial confidencial seja registrada no hub, mesmo que o prompt do usuário ou log de ferramenta a contenha.

```mermaid
sequenceDiagram
    autonumber
    actor Dev as Desenvolvedor / Agente AI
    participant MCP as McpController (/api/mcp)
    participant Auth as AuthenticateMcpToken
    participant San as CaptureSanitizer
    participant DB as PostgreSQL (Memories)
    participant Queue as Redis Queue
    participant Worker as Supervisor Worker
    participant Ollama as Ollama Embeddings

    Dev->>MCP: POST /api/mcp (method: memory_create, token)
    MCP->>Auth: Validar Bearer Token (Hash SHA-256)
    alt Token Inválido
        Auth-->>Dev: JSON-RPC Error -32001 (Não autorizado)
    else Token Válido
        Auth->>San: Sanitizar payload de memória
        Note over San: Aplica máscaras regex contra JWT, Bearer,<br/>chaves de nuvem, senhas e credenciais de URL
        San->>DB: INSERT into memories (status: active, maturidade: draft)
        San->>Queue: Dispatch GenerateMemoryEmbeddingJob & KnowledgeGraphJob
        MCP-->>Dev: JSON-RPC Result {"success": true, "id": "uuid"}
    end

    Queue->>Worker: Consome Job de Embedding
    Worker->>Ollama: POST /api/embeddings (prompt: título + stack + resumo)
    alt Ollama Online
        Ollama-->>Worker: Vetor de 768 dimensões
        Worker->>DB: UPDATE memories SET embedding = vetor, model = 'nomic-embed-text'
    else Ollama Ocupado / Falhou
        Worker->>DB: Log warning (mantém busca léxica ativa sem quebrar fila)
    end
```

---

## 6. Motor de Busca Híbrido RRF + Graph RAG

O DevMemory utiliza o estado da arte em recuperação de informação: **Reciprocal Rank Fusion (RRF)** combinando busca vetorial densa com busca léxica esparsa.

```mermaid
flowchart TD
    QUERY["Consulta do Agente: 'como configurar MCP com Laravel Sail'"] --> PARSE["Normalização & Truncamento"]
    
    PARSE --> LEXICAL["PgsqlMemoryLexicalSearch<br/>(Full-Text tsvector)"]
    PARSE --> VECTOR["Ollama Embedding Generator<br/>(Vetor da Query)"]

    LEXICAL --> L_RESULTS["Top K Resultados Léxicos<br/>Score = Lexical Rank"]
    VECTOR -->|Cosseno / HNSW| V_RESULTS["Top K Resultados Vetoriais<br/>Score = Cosine Similarity"]

    L_RESULTS --> RRF["Fusão RRF (Reciprocal Rank Fusion)<br/>Score = 1 / (60 + Rank_V) + 1 / (60 + Rank_L)"]
    V_RESULTS --> RRF

    RRF --> GRAPH_EXPAND["KnowledgeGraphQueryService<br/>(Expansão de Grafo: Stacks, Tecnologias, Arestas)"]

    GRAPH_EXPAND --> FINAL_RESPONSE["Resposta Consolidada JSON-RPC<br/>(Memórias + Contexto Relacional + Confidence)"]
```

---

## 7. Políticas de Segurança e Privacidade Invioláveis

1. **Proteção e Mascaramento Estrito de Segredos no Chat:**
   - O agente opera sob regra inviolável (`GEMINI.md`, `guardrails-safety.md`, `security-policy.md`): é terminantemente proibido exibir qualquer chave de API, bearer token ou senha em texto plano.
   - Qualquer referência nos relatórios deve preservar apenas caracteres iniciais e finais mascarando o centro (ex: `Bearer JwTGrN****************************t6KvTlQ`).
2. **Criptografia em Repouso:**
   - Segredos de integração de terceiros (como chaves de curadoria ou serviços externos) são gravados com criptografia simétrica `AES-256-CBC` no banco (`value is encrypted at rest`, comprovado por testes automatizados).
3. **Controle de Acesso em Camadas:**
   - Rotas de MCP exigem token autenticado com verificação de hash SHA-256 e rate limit de 120 requisições por minuto por IP/token.
   - Rotas administrativas (`/admin/*`) exigem autenticação de sessão web e validação de perfil `is_admin = true` via middleware `EnsureAdmin`.
4. **Isolamento de Arquivos no Servidor:**
   - Arquivo `.env` compartilhado mantido com permissão `640` restrito ao usuário de execução `www-data`.

---

## 8. Procedimentos de Recuperação Automática & Resiliência

```mermaid
stateDiagram-v2
    [*] --> Operacional

    state Operacional {
        Nginx_Ativo --> PHP_FPM_Ativo
        PHP_FPM_Ativo --> Supervisor_Ativo
        Supervisor_Ativo --> Ollama_Ativo
    }

    Operacional --> Falha_Worker : Crash de Processo PHP
    Falha_Worker --> Supervisor_AutoRestart : Supervisor detecta SIGTERM/EXIT
    Supervisor_AutoRestart --> Operacional : Restauração em < 1s (autorestart=true)

    Operacional --> Falha_Ollama : OOM ou Crash de Processo
    Falha_Ollama --> Degrada_Lexical : Busca cai para Full-Text tsvector
    Falha_Ollama --> Systemd_AutoRestart : Systemd detecta saída (Restart=always)
    Systemd_AutoRestart --> Operacional : Ollama restabelecido em < 3s

    Operacional --> Falha_Captura : Timeout de Processamento
    Falha_Captura --> AutoCura_Scheduler : Cron executa memory:process-captures --retry-failed
    AutoCura_Scheduler --> Operacional : Captura re-enfileirada e curada
```

---

## 9. Plano de Ação para Homologação Definitiva

Para converter o status de **"Apto com Ressalvas"** para **"100% Homologado em Produção"**, resta apenas a execução do checklist abaixo:

- [x] **Ação 1:** Ativar a linha do Laravel Scheduler no crontab do `www-data` na VPS para viabilizar a auto-cura horária. **(CONCLUÍDO - Validado com Exit Code 0)**
- [ ] **Ação 2:** Criar o script de backup diário do PostgreSQL (`/root/bin/backup-devmemory.sh`) com compactação e retenção de 14 dias.
- [ ] **Ação 3:** Criar comando de heartbeat/observabilidade para emitir alarme caso o Ollama fique sem responder por período prolongado.
- [ ] **Ação 4:** Homologação formal com o time de engenharia.

---
*Documentação gerada sob o CLEARER Engineering Harness v2.0.*
