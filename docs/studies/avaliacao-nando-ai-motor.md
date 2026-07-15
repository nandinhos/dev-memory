# Avaliação — `nando/ai-motor` como motor do dev-memory

**Data:** 2026-07-15
**Status:** DESCARTADO para o dev-memory (decisão de 2026-07-15). O `nando/ai-motor` é um projeto beta para explorar possibilidades em OUTROS projetos; não casa com o dev-memory, cuja inteligência de curadoria já é mais capaz. Sem Filament e sem ai-motor no dev-memory. A UI administrativa evolui a partir da UI Livewire/neo existente + autenticação.
**Pacote:** `/home/nandodev/projects/teste/nando-ai-laravel` (`nando/ai-motor`) — 20 commits, ~4 dias (11/07/2026), tags v1.0.0→v1.2.0

---

## 1. Veredito

**MVP funcional e bem-feito, mas resolve o eixo errado para o dev-memory.** É um harness de *chat conversacional multi-provider*, não um motor de *extração estruturada*. Para o objetivo estratégico do hub (ingestão agnóstica — qualquer interface salva conhecimento) ele é praticamente ortogonal. **Recomendação: backlog com escopo cirúrgico**, não adoção como dependência.

## 2. O que é e como funciona

Motor de IA "persona-driven, provider-agnostic" para Laravel 13, inspirado no Bob (`qwen2.5-7b-laravel-coder`). Entrega um assistente `ndn` (arquiteto Laravel) que detecta a versão do Laravel, conhece o projeto, troca de provider e persiste histórico de chat. 42 arquivos PHP em `src/`:

- **`Providers/`** — transporte para LLMs (o núcleo relevante): `ProviderContract` + 4 implementações + `ProviderRegistry`.
- **`Chat/`** — `ChatService`, DTOs `ChatRequest`/`ChatResponse`, persistência arquivo/banco.
- **`Context/` + `Personas/`** — montagem de system prompt via detecção de contexto + persona.
- **`Console/`** — 7 comandos `ndn:*`.
- **`Vision/`** — OCR de PDF via binários externos (tesseract, poppler). Acessório.
- **`Panel/` + `Filament/`** — 4 páginas Filament. Acessório, degrada graciosamente.

### Camada de Providers
`ProviderContract` tem 4 métodos: `name()`, `chat(ChatRequest): ChatResponse`, `isAvailable()`, `defaultModel()`. O `ProviderRegistry` faz `get`/`has`/`firstAvailable` (fallback chain) e troca em runtime. O **`MiniMaxProvider` usa exatamente o mesmo endpoint Anthropic-compatível** (`/v1/messages`, `x-api-key`, `anthropic-version`) que o `AnthropicCurationEngine` do dev-memory já usa.

**`ChatResponse` retorna string crua** — sem structured output, sem JSON schema, sem retry/reparo. Grep em toda a `src/` por `json_schema|response_format|structured|tool_use|->retry(` retorna zero.

## 3. Maturidade

| Sinal | Estado |
|-------|--------|
| História | 20 commits, ~4 dias; tags v1.0.0→v1.2.0 |
| Testes | 71 passando / 162 asserções, Pest 4.7, 3 camadas (unit/feature/integration) |
| CI | Pint + PHPStan nível 6 + testes + cobertura Codecov |
| Deps de produção | **Enxutas** — Filament, Pest, PHPStan todos em `require-dev`; só illuminate/* + symfony/http-client |
| Fraquezas | `minimum-stability: dev`; CI sem matriz Laravel (alega 10–13, testa só 13); doc drift (README cita pasta `Knowledge/` inexistente; phpstan exclui `Bridges/Devorq/*` inexistente); `LaravelAiSdkProvider` morto em L13 (laravel/ai é L12) |

**Classificação: MVP funcional** — nem protótipo, nem pronto-para-produção.

## 4. Fit com dev-memory

O `ProviderContract` **não substitui** o `KnowledgePreparationEngine`: são níveis diferentes. `prepare(): LessonDraft` retorna DTO **validado**; `chat(): ChatResponse` retorna **string crua**. O ai-motor poderia no máximo **alimentar** (ser o transporte HTTP) do `AnthropicCurationEngine`, nunca substituir a interface.

O método pivô do dev-memory — `completeJson(systemPrompt, userContent, validator)` com loop de reparo de até 3 tentativas, extração de JSON tolerante a code fences, `prompt_version` e auditoria (`lastMeta`) — **não tem equivalente no ai-motor**.

| Recurso que o dev-memory precisa | ai-motor entrega? |
|----------------------------------|-------------------|
| Structured output validado | Não |
| Retry/reparo dirigido por erro de schema | Não |
| Auditoria com prompt_version/attempts | Parcial (só tokens/model/duração) |
| Transporte MiniMax `/v1/messages` | **Sim, idêntico** |
| Troca de provider runtime + fallback | **Sim, bom** |
| Abstração multi-provider (Ollama, cloud) | **Sim** |

### Descompasso estratégico
O objetivo do hub é o **eixo de ingestão** (qualquer interface empurra conhecimento para dentro — API `/api/ingest`, MCP remoto). O ai-motor atua no **eixo de saída** (como o hub chama modelos) — que o dev-memory **já resolveu** via a interface `KnowledgePreparationEngine`. São eixos perpendiculares.

### Risco de acoplamento
`composer require nando/ai-motor` auto-boota migrations (`ai_motor_chat_*`) e os 7 comandos `ndn:*` no host via ServiceProvider incondicional. Indesejável para o hub.

## 5. Recomendação prática (se um dia fizer sentido)

Não instalar como dependência. Se surgir necessidade de pluggability de provider (ex.: reparar via Ollama local, alternar MiniMax↔Claude por config), **extrair apenas** `ProviderContract` + `ProviderRegistry` + `MiniMaxProvider`/`OllamaProvider` + os DTOs `ChatRequest`/`ChatResponse`, e refatorar o `AnthropicCurationEngine` para usar um `ProviderContract` como transporte — **preservando integralmente** o `completeJson()` (schema + reparo + auditoria) por cima. Captura o único ganho real (abstração multi-provider) sem herdar Vision, Filament, personas, comandos, migrations nem o roadmap não implementado.

## 6. Pontos fortes / fragilidades

**Fortes:** abstração de provider limpa e testável; `MiniMaxProvider` reutilizável sem atrito; núcleo de produção enxuto; disciplina de engenharia (CI lint+stan+test+coverage, testes em 3 camadas, uninstall testado); config por env estruturada.

**Fragilidades:** zero structured output/schema/reparo (o que o dev-memory mais precisa); `chat()` retorna string crua; auditoria pobre; instalar o pacote injeta migrations/comandos no host; doc drift e `minimum-stability: dev`.
