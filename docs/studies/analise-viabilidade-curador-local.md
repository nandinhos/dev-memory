# Análise de Viabilidade — Curador LLM Local para o dev-memory

**Data:** 2026-07-10
**Objeto:** proposta debatida com outro agente sobre adotar `bhavingajjar/qwen2.5-7b-laravel-coder` (Ollama) como "Laravel Knowledge Curator" no pipeline do dev-memory
**Relação:** complementa `estudo-hub-conhecimento-mcp.md` — esta proposta detalha as fases F3 (captura/classificação) e F5 (validação) daquele roadmap
**Status:** análise verificada contra o código real, a página do modelo e o banco de dados atual

---

## 1. Veredito

A proposta está **conceitualmente correta e bem arquitetada** — o princípio central ("o modelo prepara; contratos validam estrutura; fontes oficiais validam conteúdo; políticas determinísticas decidem") é exatamente o desenho certo e deve ser adotado.

Porém, a verificação de fatos revelou **quatro problemas materiais** que mudam decisões concretas:

1. **O modelo recomendado é a escolha errada** — e o próprio pipeline da proposta o anula (§3.1).
2. **A alternativa óbvia nunca foi considerada** — curadoria via API (Claude Haiku) custa centavos e a proposta não a compara (§3.2).
3. **A premissa de hardware não foi verificada** — a proposta assume que a VPS roda um 7B sem nunca perguntar os specs (§3.3).
4. **Os contratos não batem com o schema real** do dev-memory — categorias e status divergem do código em produção (§3.4).

Além disso, a proposta está **superdimensionada para escala pessoal** em pontos específicos (§3.5), embora as *formas* que ela propõe (interfaces, contratos, auditoria) devam ser mantidas em versão enxuta.

---

## 2. Verificação de Fatos (o que foi checado, não presumido)

| Alegação da proposta | Verificação | Resultado |
|----------------------|-------------|-----------|
| O modelo existe no Ollama | Página oficial consultada | ✅ Existe: 4,7 GB, base `qwen2.5-coder` 7B, persona "Bob", licença Apache 2.0 |
| "Customização de terceiro" | Tamanho idêntico ao base (4,7 GB = mesmo peso Q4) | ⚠️ É **apenas um Modelfile** (system prompt + temperature 0.3) sobre o base — **não é fine-tune** |
| Confiabilidade do publicador | Página do modelo | 🔴 **65 downloads**, atualizado há 1 semana — zero vetting de comunidade, alvo móvel |
| Ollama suporta structured outputs (`format` + JSON Schema) | Conhecido/documentado | ✅ Correto |
| Contexto Qwen2.5-Coder 7B ~32k | Model card HF | ✅ Correto |
| Categorias propostas compatíveis com dev-memory | `app/Enums/MemoryType.php` lido | 🔴 Divergem: código tem 3 casos (`error`, `lesson`, `best_practice`); proposta usa 6, incluindo `good_practice` (nome errado) |
| Status de validação documental | `app/Enums/ValidationStatus.php` lido | 🔴 Enum proposto (5 estados) não existe; atual tem 3 (e ainda falta o `superseded` já conhecido) |
| Integração Ollama existente | `grep -ri ollama app/ config/` | ✅ Zero referências — integração greenfield |
| Specs da VPS suportam 7B | Medido pelo Nando em 2026-07-10 (`free -h && nproc` em srv084270) | 🔴 **7,8 GB RAM · 4 vCPU · sem GPU** — 7B co-hospedado com o hub **não cabe** (ver §3.3) |
| Volume para o estágio de Skills | Query no banco real | ✅ **25 memórias validadas com recurrence ≥ 3** hoje — o estágio de Skill Candidates teria matéria-prima imediata (com ressalva, ver §4.5) |
| MiniMax como motor (API Anthropic-compat) | Teste real executado em 2026-07-10 | ✅ HTTP 200 com **MiniMax-M2.5** (plataforma global); smoke test de curadoria retornou LessonDraft JSON válido — campos completos, categoria correta |

---

## 3. Onde a Proposta Erra ou Precisa de Correção

### 3.1 O modelo errado — use o `qwen2.5-coder:7b` oficial

Descoberta central: o modelo `bhavingajjar/...` é o **base Qwen2.5-Coder 7B com um system prompt embutido** ("Bob, senior PHP & Laravel assistant") e parâmetros no Modelfile. Não há pesos novos.

O pipeline da própria proposta **anula essa customização**: o `OllamaClient` proposto envia `system` prompt próprio (via `LessonPromptFactory`) e `temperature: 0.1` — no Ollama, mensagens `system` da requisição **substituem** o SYSTEM do Modelfile, e `options` da requisição sobrescrevem os parâmetros. Ou seja: a única coisa que o modelo de terceiro adiciona é exatamente o que o pipeline descarta.

**Consequência:** toda a seção de "segurança da cadeia de fornecimento" (10 exigências: inspecionar Modelfile, fixar digest, comparar com base, rollback...) resolve um problema que **não precisa existir**. Basta usar o [`qwen2.5-coder:7b` oficial da biblioteca Ollama](https://ollama.com/library/qwen2.5-coder:7b) — mesma capacidade, proveniência limpa, mantido pela equipe Ollama/Qwen — com o system prompt do pipeline. Fixar por digest continua sendo boa prática, mas o risco de terceiro desaparece.

### 3.2 A alternativa não considerada — curadoria via API

A proposta compara o Qwen 7B com... nada. Para escala pessoal, a comparação obrigatória é com um modelo de API pequeno (ex.: Claude Haiku 4.5):

| Critério | Ollama local (7B Q4, CPU) | API (Haiku 4.5) |
|----------|---------------------------|------------------|
| Custo por captura (~3k in / 1k out) | R$ 0 marginal | ~US$ 0,008 (US$ 1/US$ 5 por MTok) |
| Custo mensal (500 capturas) | R$ 0 | < US$ 4 |
| Qualidade de extração estruturada | razoável (7B) | superior |
| Latência por captura | 1–3 min (CPU) | 5–15 s |
| Privacidade | total (nada sai) | conteúdo sai (mitigado pela sanitização prévia) |
| Infra a manter | Ollama + RAM + disco | zero |
| Funciona offline | sim | não |

**Atualização 2026-07-10 — terceiro candidato testado e aprovado**: o Nando assina **MiniMax** (token plan, plataforma global), cuja API é compatível com Anthropic (`https://api.minimax.io/anthropic`). Teste real: HTTP 200 com **MiniMax-M2.5**; o smoke test de curadoria retornou LessonDraft em JSON puro, parseável, todos os campos presentes e categoria válida (185 tokens in / 570 out). Posição na matriz: **custo marginal zero** (assinatura já paga), qualidade de modelo de fronteira, latência de segundos, e a mesma implementação `AnthropicEngine` serve para MiniMax e Claude mudando só `base_url`/`model`. Config pronta em `config/services.php` (`services.minimax`), chave no `.env` (fora do git).

O argumento honesto pró-local é **privacidade/soberania** (capturas podem conter código de clientes) — não qualidade, custo nem simplicidade: com o MiniMax na mesa, o custo marginal da API também é zero. A decisão é sua, mas deve ser tomada às claras, não por omissão.

**Resolução recomendada: não decidir no papel — medir.** A interface `KnowledgePreparationEngine` (a melhor ideia da proposta) torna o motor uma configuração. O piloto (§5, fase P1) roda os mesmos ~30 casos nos dois motores e os números decidem. Nota: o ecossistema Laravel atual tem SDK de AI oficial com abstração de provedores, e o Ollama expõe endpoint compatível com OpenAI (`/v1`) — a interface pode sair quase de graça.

### 3.3 Hardware — medido: a VPS não comporta o 7B junto do hub

Specs medidos em 2026-07-10 (`free -h && nproc`, host `srv084270`): **7,8 GiB de RAM** (2,1 já em uso, 5,7 disponíveis), **swap de 2 GiB**, **4 vCPUs**, sem GPU.
> Nota: as specs do projeto citam `srv163217`; o comando foi executado em `srv084270` — confirmar qual VPS será o hub.

Conta do 7B Q4 em CPU: ~4,7 GB de pesos + 0,5–1 GB de KV cache (ctx 8–16k) + overhead do runtime ≈ **6–6,5 GB** — acima dos 5,7 GB disponíveis, e isso *antes* de o hub crescer (PostgreSQL + PHP-FPM + Redis + workers). Resultado: swap e OOM em operação real. Agravante de CPU: a inferência satura as 4 vCPUs a ~4–8 tokens/s (um `LessonDraft` de ~800 tokens = **2–3,5 min**), degradando as respostas do MCP/UI durante cada curadoria.

**Veredito: co-hospedar o 7B nesta VPS está descartado.** Caminhos viáveis, agora ordenados por dados:
- **(a) Motor API** — zero exigência de hardware; **MiniMax-M2.5 já testado e aprovado** (assinatura paga → custo marginal zero) ou Haiku (< US$ 4/mês); o mais simples e de maior qualidade.
- **(b) `qwen2.5-coder:3b` na própria VPS** — ~1,9 GB de pesos, ~2,8 GB totais: **cabe com folga**. Qualidade de classificação é a incógnita → entra como candidato no piloto P1 e os números decidem.
- **(c) 7B na máquina WSL como worker da fila** — o hub enfileira; o worker processa quando a máquina estiver ligada. Aceitável porque curadoria é assíncrona por natureza.
- **(d) Híbrido** — API por padrão, local para capturas marcadas como sensíveis.

### 3.4 Contratos desalinhados do schema real

Ajustes obrigatórios para a proposta encaixar no código existente:

| Item da proposta | Realidade no código | Correção |
|------------------|--------------------|----------|
| `category: good_practice` | `MemoryType::BEST_PRACTICE = 'best_practice'` | Contrato adota o nome do código |
| `workaround`, `architecture_decision`, `anti_pattern` | Não existem | Expandir `MemoryType` (+3 casos) **e** atualizar a CHECK constraint criada em `2026_03_22_155538` (ativa no Postgres) |
| `DocumentationValidationStatus` (5 estados) | Não existe | Criar enum + coluna `doc_validation_status` — **ortogonal** ao `ValidationStatus` atual, não substituto |
| Status `superseded` | Já referenciado no sync, ausente do enum | Já é dívida conhecida (F0 do estudo anterior) — resolver junto |
| Tabela de capturas imutáveis | Não existe | Nova tabela `captures` (raw + sanitized + idempotency_key único) |
| Auditoria de execuções | Não existe | Nova tabela `curation_executions` (modelo, digest, prompt_version, hashes, duração) |

### 3.5 Superdimensionamento para escala pessoal

A proposta desenha para uma equipe/produto. Para um sistema pessoal com 46 memórias e captura de dezenas/semana:

| Proposta | Right-size |
|----------|-----------|
| 3 componentes independentes (orchestrator / runtime / policy-engine) | **Mesma separação, como classes/namespaces** no próprio Laravel (`Domain\Knowledge\...`); o Ollama já é container isolado. Virar serviços só se um dia houver múltiplos consumidores |
| Suíte de avaliação com 180 casos antes de começar | **~30 casos no piloto** (10 válidas, 5 incompletas, 5 com secrets, 5 fora de Laravel, 5 prompt injection), crescendo com uso real |
| Roteador multi-modelo por domínio (TS, DevOps, generalista...) | **Adiar.** Seu acervo é ~80% Laravel/PHP (top stacks: Laravel, Livewire, Alpine, Docker). Um motor + regra simples "fora de PHP/Laravel → fila de revisão humana" cobre o início |
| Policy engine com permissões/score/bloqueio completo | Classe `PromotionPolicy` determinística com 4-5 regras; cresce com necessidade |
| Critério "schema válido ≥ 99%" | ≥ 95% no piloto; 99% como critério de *automação* (sem humano no loop) |

O que **não** enxugar: sanitização (secret leak = 0 é inegociável), captura imutável, validação dupla de schema, idempotência, auditoria de execuções. São baratos e estruturais.

---

## 4. O que a Proposta Acerta (adotar como está)

1. **Princípio curador-não-autoridade** — o modelo nunca decide promoção, validade documental ou execução; políticas determinísticas decidem. É a espinha dorsal certa.
2. **Captura bruta imutável** — a versão da IA nunca substitui o original. Essencial para auditoria e reprocessamento quando o motor melhorar.
3. **Sanitização determinística antes do LLM** — secrets/PII mascarados por regex/detectores, não por IA. (Vale para motor local **e** API.)
4. **Validação documental fundamentada (RAG)** — entregar ao modelo os trechos recuperados do Context7 e pedir comparação restrita ("não use conhecimento externo") em vez de perguntar "está correto?". Reduz alucinação estruturalmente. Encaixa 1:1 na fase F5 do estudo anterior.
5. **Crítica à promoção automática por 3 recorrências** — correta: recorrência mede *relevância*, não *validade*. O funil `candidato → curadoria → validação estrutural → documental → escopo → testes → política` está certo.
6. **Score de recorrência composto** (semântica + erro normalizado + causa raiz + versão + padrão de solução) com regras de independência e chave de idempotência — resolve o ponto fraco real da dedup por embedding puro.
7. **Interface `KnowledgePreparationEngine`** — desacopla domínio do provedor; é o que torna a decisão de motor reversível.
8. **Rastreabilidade por execução** (modelo, digest, prompt_version, schema_version, hashes) — sem isso o sistema não é auditável.

---

## 5. Plano Adequado (integrado ao roadmap F0–F7)

> Pré-requisito herdado: F0 (higiene — credenciais fora do código, enum `superseded`, destino de `devorq_lessons`) continua sendo o primeiro passo de tudo.

### P0 — Decisão de motor *(½ dia, decisão sua)*
- ~~Specs da VPS~~ ✅ medidos em 2026-07-10: 7,8 GB / 4 vCPU — **7B co-hospedado descartado** (§3.3).
- Confirmar qual VPS é o hub (`srv084270` medida vs. `srv163217` das specs).
- Definir postura de privacidade: capturas podem sair para API após sanitização? (sim/não/depende)
- Saída: matriz do §3.2 + veredito do §3.3 → escolha provisória de motor (reversível por design).

### P1 — Piloto de curadoria *(1–2 dias, sem tocar produção)*
- Interface `KnowledgePreparationEngine` + DTO `LessonDraft` + schema JSON **adaptado aos enums reais** (§3.4).
- Implementações a comparar (mesmos ~30 casos): `AnthropicEngine` apontando para **MiniMax-M2.5** (smoke test aprovado; config pronta em `services.minimax`) e opcionalmente para Haiku; `OllamaEngine` com `qwen2.5-coder:3b` (único que cabe na VPS — §3.3) e/ou `7b` na WSL. Digest fixado nos modelos locais.
- Comando `php artisan memory:curate --dry-run` reprocessando as 46 memórias existentes + mini-eval de ~30 casos sintéticos.
- **Gates go/no-go**: schema_validity ≥ 95% · secret_leak = 0 · concordância de classificação ≥ 85% vs. sua curadoria manual · comparativo lado a lado dos motores.
- Se nenhum motor passar: modelo vira "assistente de rascunho" na UI (sugestão editável), não estágio automático — exatamente o fallback que a proposta prevê.

### P2 — Ingestão imutável *(2–3 dias; = fase F3 do estudo)*
- Migration: tabela `captures` (payload bruto imutável, sanitized_content, idempotency_key único SHA-256, source, trigger, metadata).
- `CaptureSanitizer` determinístico (padrões de secrets, PII, tamanho, binário) com teste de vazamento zero.
- Expansão de `MemoryType` (+3 casos) com migration da CHECK constraint; enum `DocumentationValidationStatus`; coluna nova em `memories`.

### P3 — Pipeline assíncrono *(2–3 dias)*
- Cadeia de jobs: `capture → sanitize → curate (engine) → validate schema (2 reparos, depois processing_failed) → dedup → persist draft`.
- Tabela `curation_executions` (auditoria completa por execução).
- `PromotionPolicy` como classe de domínio (não serviço): regras de status, score mínimo, quando exigir humano.
- Ollama (se escolhido) em container na rede interna, porta não exposta — o YAML da proposta serve como está.

### P4 — Validação documental *(2–3 dias; = fase F5 do estudo)*
- Adapter Context7 (retrieve por tecnologia/versão/conceito) → prompt de comparação fundamentada → contrato de veredito (claims/supported/conflicts).
- Somente `confirmed` gera rótulo automático; `partially_confirmed`/`inconclusive` → fila de revisão na UI (bulk actions já planejadas na spec de captura §4.3).

### P5 — Recorrência composta *(1–2 dias)*
- Implementar o score composto e as regras de independência da proposta; calibrar pesos com os dados reais do acervo.
- Migrar a dedup do import (Levenshtein) para o mesmo mecanismo.

### P6 — Skill Candidates *(depois de P4+P5 estabilizarem)*
- **Nota do banco real**: já existem 25 memórias validadas com `recurrence_count ≥ 3` — mas esses contadores vieram da deduplicação do import histórico, **não** passaram pelo critério de independência do P5. Portanto: a **primeira leva é curadoria humana assistida** (o motor gera o SkillCandidate como rascunho, você aprova), e o funil automático só vale para recorrências contadas pelas novas regras.
- Contrato SkillCandidate da proposta adotado como está; publicação via git (fonte de verdade das skills, F6 do estudo anterior).

**Dependências**: P1 não depende de nada (roda local). P2–P3 podem preceder a inversão do hub (F1) rodando localmente, mas o endpoint `/api/ingest` remoto só faz sentido após F1. P4 depende de P3. P6 depende de P4+P5.

---

## 6. Decisões que Só Você Pode Tomar

1. **Motor**: MiniMax-M2.5 (testado ✓, custo marginal zero) vs. 3b na VPS vs. 7B na WSL vs. híbrido — o piloto P1 decide com números. (7B na VPS eliminado pelos specs; MiniMax larga na frente.)
2. **Qual VPS é o hub** — `srv084270` (medida: 7,8 GB/4 vCPU) ou `srv163217` (citada nas specs)?
3. **Postura de privacidade** — capturas sanitizadas podem sair para API?
4. **Expansão do `MemoryType`** (+workaround/architecture_decision/anti_pattern) vs. campo `maturity` ortogonal (sugerido no estudo anterior) — recomendo a expansão do type + `maturity` depois, mas afeta UI/filtros/testes existentes.
5. **Primeira leva de Skills**: aproveitar já as 25 candidatas com curadoria humana, ou esperar o mecanismo de independência (P5)?

---

## 7. Fontes

- [Página do modelo bhavingajjar/qwen2.5-7b-laravel-coder](https://ollama.com/bhavingajjar/qwen2.5-7b-laravel-coder) (verificada: 4,7 GB, persona via Modelfile, 65 downloads)
- [qwen2.5-coder:7b oficial — Ollama Library](https://ollama.com/library/qwen2.5-coder:7b)
- [Qwen2.5 7B: tamanho e requisitos](https://localaimaster.com/models/qwen-2-5-7b)
- [Qwen/Qwen2.5-Coder-7B-Instruct — Hugging Face](https://huggingface.co/Qwen/Qwen2.5-Coder-7B-Instruct)
- Código real: `app/Enums/MemoryType.php`, `app/Enums/ValidationStatus.php`, migration `2026_03_22_155538` (CHECK constraints), banco SQLite local (46 memórias, distribuição de recorrência)
- Estudo anterior: `docs/studies/estudo-hub-conhecimento-mcp.md`
