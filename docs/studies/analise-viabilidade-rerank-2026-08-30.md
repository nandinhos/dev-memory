# Análise de Viabilidade — Rerank na busca do dev-memory

**Data:** 2026-08-30
**Objeto:** avaliar a inserção de uma etapa de *rerank* no pipeline de recuperação (`MemorySearchService`) com o objetivo de aumentar a qualidade das respostas entregues aos agentes via MCP
**Relação:** evolui a camada descrita em `docs/reports/analise-2026-08-22.md` §5 (Busca e Recuperação — híbrida com RRF, nota 0.85)
**Continuação:** [`analise-viabilidade-harness-planejamento-2026-08-30.md`](analise-viabilidade-harness-planejamento-2026-08-30.md) — o degrau 1 daqui só chega ao planejamento se o `hub_briefing` passar a usar o `MemorySearchService` · e [`analise-viabilidade-devorq-2026-08-30.md`](analise-viabilidade-devorq-2026-08-30.md) fecha a trilogia no lado da execução
**Status:** análise verificada contra o código real, o banco do ambiente Docker e os números de produção do `STATUS.md`

---

## 1. Veredito

**Viável — mas não como um passo único.** A resposta honesta não é "sim/não", é uma **escada de três degraus**, e o primeiro degrau é gratuito, determinístico e entrega mais ganho por risco do que qualquer reranker neural no volume atual.

| Degrau | O que é | Custo | Ganho esperado | Recomendação |
|---|---|---|---|---|
| **0. Golden set** | ~25 pares `query → ids esperados` + medição de MRR@10 / nDCG@10 **antes** de mudar código | 1/2 sessão | Nenhum direto — mas sem ele nada aqui é falseável | **Bloqueante** |
| **1. Rerank por features** | Re-scoring determinístico do pool fundido usando sinais **já curados e hoje ignorados** (`validation_status`, `recurrence_count`, `doc_validation_status`, match de stack, recência; `maturity` entra com peso mas está inerte — §3.3) | 1 sessão, zero dependência externa | Alto no corpus atual | **Fazer** |
| **2. Rerank neural (cross-encoder API)** | Jina / Voyage / Cohere sobre o top-60 | 1 sessão + nova credencial + latência de rede | Marginal com ~110–145 memórias; cresce com o corpus | **Adiar** até o golden set provar o teto do degrau 1 |

**Recomendação:** executar 0 → 1 agora; reavaliar o degrau 2 quando o corpus passar de ~500 memórias **ou** quando o golden set mostrar o degrau 1 saturado.

---

## 2. Como a busca funciona hoje

`app/Services/Search/MemorySearchService.php:23` — pipeline de dois retrievers com fusão RRF:

```
query ──┬─→ lexical  (Pgsql tsvector / Sqlite LIKE)  → top-N ──┐
        └─→ semantic (pgvector / cosseno em PHP)     → top-N ──┴─→ fuse() RRF k=60 → take(limit)
```

- `$candidateLimit = min(max($limit * 3, 20), 100)` por retriever (`MemorySearchService.php:41`).
- Fusão: `1 / (60 + posição)` somado entre as duas listas; desempate por `recurrence_count` (`MemorySearchService.php:76-113`).
- Retorna `search_mode`: `hybrid | semantic | lexical | filter`.

### Os três call-sites (e por que eles não são iguais)

| Call-site | Arquivo | `limit` | Pool único | Consumidor | Rerank compensa? |
|---|---|---|---|---|---|
| MCP `memory_search` | `app/Mcp/MemoryMcpServer.php:447` | 10 | ≤60 | **LLM** — qualidade >> latência | **Sim, prioritário** |
| Dedup de captura | `app/Services/Curation/RecurrenceScorer.php:71` | 30 (`CANDIDATE_LIMIT`) | ≤180 | job assíncrono | Sim (latência irrelevante) |
| Lista da UI | `app/Livewire/MemoryList.php:179` | **120** | ≤200 | humano paginando 12/pág. | **Não** |

> O pool do dedup é **3× maior** que o do MCP (`min(max(30×3,20),100) = 90` por retriever). Se um dia o degrau 2 chegar nesse call-site, o custo por chamada é ~3× o calculado em §4.

`HubBriefingService` **não** passa pelo `MemorySearchService` (consulta `Memory::query()` direto, `HubBriefingService.php:59,79`) — fora do escopo.

---

## 3. Achados que mudam a decisão

### 3.1 O corpus atual é pequeno demais para um cross-encoder brilhar

Produção: **110–145 memórias** — o `STATUS.md` se contradiz (cabeçalho diz 145; a tabela na linha 61 e o relato do Gate 3.4 na linha 98 dizem 110; o "145" da linha 95 é contagem de TOC de um `pg_dump`, não de memórias). A `analise-2026-08-22.md:39`, do mesmo dia, lê 110. **Confirmar contra prod** (§7 passo 1). Ambiente Docker local: 106.

Em qualquer ponto dessa faixa, com um pool de ≤60 candidatos o retriever já viu **40–55% do corpus inteiro** em cada busca. Rerank neural resolve o problema de "ordenar 100 candidatos entre 10 milhões de documentos" — não é esse o problema aqui. O gargalo de qualidade hoje não é *ordenação de candidatos*, é **ausência de sinal de qualidade editorial na ordenação** (§3.3).

Isso muda quando a captura contínua via hooks escalar o corpus. O degrau 2 é uma decisão de **quando**, não de **se**.

### 3.2 `fuse()` trunca antes da hora — refactor obrigatório

`MemorySearchService.php:111` faz `->take($limit)` **dentro** da fusão. Qualquer rerank precisa operar sobre o pool **não truncado**. Portanto rerank **não pode** ser um wrapper sobre o retorno de `search()` — quando aquele array volta, os candidatos já foram descartados.

Refactor mínimo: `fuse()` devolve a lista completa fundida → nova etapa `rerank()` → `take($limit)`.

### 3.3 Sinais de qualidade já curados não participam do ranking

O `fuse()` ordena **só por RRF**, usando `recurrence_count` apenas como desempate. Tudo o que o hub sabe sobre a *qualidade editorial* de uma memória fica de fora. Medido no banco Docker (106 memórias):

| Sinal | Variância hoje | Rende no degrau 1? |
|---|---|---|
| `validation_status` | **39 validated / 67 pending** (prod: 88 pending / 22 validated) | ✅ Imediato — é o sinal mais forte disponível |
| `recurrence_count` | **35 memórias com > 1** | ✅ Imediato — hoje só desempata, deveria pesar |
| `doc_validation_status` | preenchido pelo `DocumentationValidator` | ✅ Imediato |
| match de stack / recência | derivável sempre | ✅ Imediato |
| `maturity` | ⚠️ **106/106 = `provisional`** — variância zero | ❌ **Inerte hoje** |

Ou seja: uma memória `pending`, nunca vista de novo e sem validação documental compete em pé de igualdade com uma `validated` recorrente. **Um agente consumindo `memory_search` pode receber a versão pior de um conhecimento que o hub já sabe qual é a melhor.** Corrigir isso é rerank — só que determinístico, grátis e testável em SQLite. É o degrau 1.

> ⚠️ **Atenção a quem implementar:** o `maturity` (`workaround → provisional → recommended → canonical → consolidated`) é conceitualmente o sinal mais rico do hub, mas **hoje multiplica por constante** — a promoção ainda não foi exercida em volume. Incluir o fator na fórmula, com peso configurável, e **não contar com ganho dele agora**. O degrau 1 se sustenta em `validation_status` + `recurrence_count` + stack + recência. A distribuição de `maturity` em prod não foi verificada; confirmar junto com §7 passo 1.

### 3.4 A busca semântica está morta no ambiente Docker

`embedding_model` está vazio nas 106 memórias do container → `withEmbedding()` não casa nada → toda busca local cai para `lexical`. Em produção a análise de 2026-08-22 confirmou modo `semantic` real, então é um problema de ambiente, não de código.

**Consequência prática:** medir rerank localmente hoje mede rerank *sobre busca lexical pura*. Rodar `GenerateMemoryEmbeddingJob` no ambiente de avaliação é **pré-requisito** do golden set.

### 3.5 Nenhum provedor já contratado oferece rerank

- **MiniMax** (já usado para curadoria e embeddings): **não há endpoint de rerank documentado** na API pública. Não assumir que existe.
- **Ollama** (`OLLAMA_HOST` já configurado): **não tem `/api/rerank` nativo** — expõe só a camada de embedding, não a de classificação dos cross-encoders. Modelos como `bge-reranker-v2-m3` estão no registry, mas usá-los exige um serviço intermediário (FastAPI/TEI). Não é "ligar uma env var".

Portanto o degrau 2 significa **novo fornecedor + nova credencial + novo ponto de falha**. Isso pesa contra fazê-lo agora.

---

## 4. Os degraus em detalhe

### Degrau 0 — Golden set (bloqueante)

"Aumentar a qualidade" é infalseável sem baseline. Antes de tocar em ranking:

1. Escolher ~25 consultas reais (as que agentes de fato fazem via MCP), com os ids esperados.
2. Popular embeddings no ambiente de avaliação (§3.4).
3. Medir **MRR@10** e **nDCG@10** do RRF atual.

Sem esse número, qualquer alteração de ranking vira opinião — e ranking é exatamente a área onde "parece melhor" engana com mais facilidade.

### Degrau 1 — Rerank determinístico por features

Re-scoring do pool fundido:

```
score_final = rank_score_RRF × w_validação × w_recorrência × w_doc × (1 + boost_stack) × decaimento_recência × w_maturity
                               └──────────── sinais com variância hoje ────────────┘              └ inerte por ora ┘
```

- **Zero** dependência externa, latência ~0, 100% testável com fixtures em SQLite.
- Pesos em `config/` — ajustáveis contra o golden set sem redeploy de lógica.
- Aproveita investimento já feito (`maturity`, `MaturityPolicy`, `DocumentationValidator`, `RecurrenceScorer`).
- Efeito colateral positivo: melhora o top-K do `RecurrenceScorer`, ou seja, **melhora o dedup de captura de graça**.

### Degrau 2 — Rerank neural via API

Custo por busca — 60 candidatos × ~300 tokens (descrição média real: **1.097 chars**, máx. 1.855):

| Provedor | Preço | Por busca | Por 1.000 buscas |
|---|---|---|---|
| Jina reranker v2 | $0,02 / 1M tokens | ~$0,00036 | **~$0,36** |
| Voyage rerank-2.5 | $0,05 / 1M tokens | ~$0,0009 | ~$0,90 |
| Cohere Rerank 3.5 | $2,00 / 1.000 buscas | $0,002 | $2,00 |

**Custo não é o obstáculo** — no volume atual isso é ruído. Os obstáculos reais são: (a) novo fornecedor no caminho crítico do MCP, (b) +100–300 ms por busca, (c) ganho não comprovado com 145 documentos.

### Alternativa avaliada e descartada: LLM-as-reranker via MiniMax

Reutilizaria `MINIMAX_BASE_URL` e o idiom de saída estruturada do `AnthropicCurationEngine` — sem credencial nova. Mas 2–6 s de latência inviabiliza o caminho síncrono do MCP. **Só faz sentido dentro do `RecurrenceScorer`** (assíncrono), se o degrau 1 não bastar para o dedup.

---

## 5. Contratos que mudam

| Superfície | Impacto | Mitigação |
|---|---|---|
| `search_mode` | Asserção em `MemorySearchServiceTest.php:55,74` e `PostgresNativeSearchTest.php:36` | **Não** criar novo valor de modo; adicionar chave separada `reranked: bool` — quebra menos |
| Forma de cada linha | Os testes acessam `['memory']` e `['rank_score']` diretamente (`MemorySearchServiceTest.php:60,75`, `PostgresNativeSearchTest.php:37`) | O refactor do `fuse()` (§3.2) **deve preservar** as chaves `memory`, `similarity`, `lexical_score` e `rank_score`; `rerank_score` entra como chave adicional |
| Payload MCP `memory_search` | `MemoryMcpServer.php:451-470` já expõe `similarity` + `rank_score`; somar `rerank_score` | Aditivo; outros projetos consomem por rede — não remover campos existentes |
| Degradação | Reranker fora do ar **não pode** derrubar `memory_search` | Seguir o precedente de `EmbeddingGeneratorService::generate()` → `null`: em erro/timeout, devolver a ordem RRF intacta, logar warning, sinalizar na resposta |
| Cache | Repetição de query+candidatos | Espelhar o padrão `embedding_hash`: chave = `hash(query + ids ordenados + modelo)`. Redis já existe no Docker |
| Assinatura | Rerank **não pode** ser incondicional (§2 — a UI pede 120) | `search(string $query, array $filters, int $limit, bool $rerank = false)`; ligar primeiro só no MCP |

---

## 6. Riscos

1. **Otimizar o que não é o gargalo** — se as respostas ruins vêm de descrições mal curadas ou memórias `pending` demais, rerank reordena lixo com mais precisão. O golden set expõe isso antes do gasto.
2. **Regressão silenciosa** — rerank pode piorar recall em consultas de termo exato (nome de erro, comando). Guardar o modo RRF puro como fallback comparável.
3. **Acoplamento do dedup** — mexer no ranking muda o top-K do `RecurrenceScorer` e, portanto, o comportamento de deduplicação. Os floors (`TEXT_FLOOR=0.55`, `TOTAL_FLOOR=0.50`) precisam ser reavaliados junto.

---

## 7. Próximos passos

1. **Confirmar o número real de memórias em prod** e a distribuição de `maturity`/`validation_status` (§3.1, §3.3) — 2 queries; ajustar o `STATUS.md`, que está inconsistente.
2. Popular embeddings no ambiente de avaliação (§3.4) — sem isso nada é mensurável.
3. Construir o golden set (~25 consultas) e medir MRR@10 / nDCG@10 do RRF atual.
4. Rodar `/bc-harness:plan` para o degrau 1 → `.spec/features/rerank-por-features/`.
5. Reavaliar o degrau 2 com o baseline em mãos.

**Fontes de custo/capacidade:** [Cohere Rerank 3.5](https://cloudprice.net/models/cohere-rerank-3-5) · [Voyage rerank-2.5](https://docs.voyageai.com/docs/pricing) · [Jina Reranker](https://jina.ai/reranker/) · [Ollama sem endpoint de rerank](https://github.com/ollama/ollama/issues/10467) · [MiniMax API overview](https://platform.minimax.io/docs/api-reference/api-overview)
