# Memória semântica e knowledge graph

## Decisão

A evolução da recuperação de conhecimento segue esta ordem:

1. manter um único espaço vetorial canônico por ambiente;
2. combinar busca lexical e vetorial com uma fusão de rankings real;
3. projetar relações governadas em um knowledge graph no PostgreSQL;
4. só então adicionar extração de relações por IA.

O PostgreSQL continua como fonte de verdade. O grafo usa tabelas relacionais e não introduz, neste estágio, outro banco ou sincronização distribuída.

## 1. Espaço vetorial canônico

O espaço vetorial é identificado por `provider/model`, persistido em `memories.embedding_model`. O hash do conteúdo inclui esse identificador, portanto trocar provedor ou modelo invalida corretamente o embedding anterior.

Invariantes:

- um ambiente possui um único `EMBEDDING_PROVIDER` ativo;
- a dimensão deve ser exatamente `EMBEDDING_DIMENSIONS`;
- vetores não são preenchidos, truncados ou misturados entre modelos;
- uma falha do provedor mantém a busca lexical disponível, sem fabricar um vetor;
- a busca vetorial filtra por `embedding_model` antes de calcular similaridade.

Variáveis principais:

```dotenv
EMBEDDING_PROVIDER=minimax
EMBEDDING_DIMENSIONS=1536
MINIMAX_EMBED_MODEL=embo-01

# Alternativa local
# EMBEDDING_PROVIDER=ollama
# OLLAMA_HOST=http://host.docker.internal:11434
# OLLAMA_EMBED_MODEL=nomic-embed-text
```

Depois de alterar o espaço canônico, regenere os vetores:

```bash
docker compose exec app php artisan memory:embeddings:backfill --sync
```

Use `--force` somente para reprocessar também registros que já estejam no espaço configurado.

## 2. Busca híbrida verdadeira

A busca executa duas recuperações independentes:

- lexical: `websearch_to_tsquery`, coluna `tsvector` gerada e índice GIN no PostgreSQL;
- semântica: distância vetorial do pgvector, restrita ao mesmo `embedding_model`.

Os rankings são combinados por Reciprocal Rank Fusion, com `k = 60`. Isso evita comparar diretamente escalas incompatíveis de `ts_rank_cd` e distância vetorial. O retorno informa `search_mode` (`hybrid`, `semantic`, `lexical` ou `filter`) e `rank_score`.

Se o provedor de embeddings estiver indisponível, a recuperação lexical continua funcionando. SQLite mantém implementações determinísticas para desenvolvimento e testes, sem fingir compatibilidade entre vetores de dimensões diferentes.

## 3. Knowledge graph governado

### Modelo

- `knowledge_nodes`: entidades canônicas, com namespace, chave estável, tipo e propriedades;
- `knowledge_edges`: relações direcionadas, origem, confiança, estado e metadados de extração;
- `knowledge_edge_evidence`: evidências rastreáveis ligadas à aresta e, quando aplicável, à memória ou captura de origem.

As relações suportadas incluem `causes`, `resolves`, `prevents`, `supports`, `contradicts`, `supersedes`, `depends_on`, `applies_to`, `derived_from` e `duplicates`.

### Governança

- somente arestas `validated` participam das consultas;
- arestas extraídas por IA sempre nascem como `proposed`;
- `contradicts` e `supersedes` exigem revisão humana, salvo quando a origem já é humana;
- autoarestas são rejeitadas no domínio e por restrição do PostgreSQL;
- toda relação publicável deve carregar origem, confiança e evidência;
- consultas respeitam o escopo: uma memória de projeto acessa o próprio projeto e globais; uma memória global não expõe memórias privadas de projeto.
- `project_id` nulo não identifica um projeto: ele não autoriza travessia entre duas memórias privadas legadas.

### Projeção atual

A primeira projeção é propositalmente determinística: cada memória vira um nó `memory`, cada item de `stack` vira um nó canônico `technology`, e a relação `applies_to` é validada com evidência da própria memória. Atualizações removem apenas arestas determinísticas que ficaram obsoletas.

Criações e alterações realizadas por `MemoryService` enfileiram a projeção. Para dados existentes:

```bash
docker compose exec app php artisan memory:graph:backfill --sync
```

O comando e o projetor são idempotentes.

## 4. Fronteira de projeto no MCP

O `project` informado por um cliente em `memory_ingest` é somente proveniência (`source_project`). A autorização vem exclusivamente do token HTTP:

- tokens comuns são emitidos vinculados a um registro em `projects`;
- uma leitura MCP comum retorna apenas memórias globais ou privadas do `project_id` do token;
- criação e captura recebem o mesmo `project_id` do token, independentemente do payload;
- promoção ou criação global exige token global de administrador;
- perfis de harness pertencem ao usuário do token e não são listados ou provisionados por outro usuário;
- stdio local permanece operação de operador do hub, fora do transporte remoto.

Registros legados sem `project_id` ficam inacessíveis para tokens comuns até associação explícita. Isso evita inferir posse por nome de projeto ou por conteúdo da memória.

### Consulta explicável

A tool MCP `memory_related` percorre somente arestas validadas, com profundidade limitada a 3. Cada resultado contém o caminho completo, direção, relação, confiança e evidências utilizadas. O limite impede consultas abertas e mantém o custo previsível.

Exemplo de argumentos:

```json
{
  "id": "<uuid-da-memoria>",
  "depth": 2,
  "limit": 20
}
```

## Operação Docker

Ordem de ativação em cada ambiente:

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan memory:embeddings:backfill --sync
docker compose exec app php artisan memory:graph:backfill --sync
```

Antes dos backfills, confirme que o worker e o provedor configurado estão saudáveis. O host `host.docker.internal` é mapeado para o host Docker no Compose, permitindo usar um Ollama executado na máquina local.

## Próximas extensões

1. extrator estruturado de entidades e relações, mantendo saída de IA como proposta;
2. fila de revisão para validar, rejeitar ou substituir arestas;
3. relações determinísticas adicionais derivadas de referências oficiais e capturas;
4. métricas de cobertura, propostas pendentes, arestas sem evidência e latência p95 das travessias;
5. deduplicação que use busca híbrida e sinais do grafo, sem promover similaridade a equivalência automaticamente.

Um banco de grafos dedicado só deve ser reconsiderado se medições reais mostrarem que as travessias limitadas e indexadas no PostgreSQL não atendem à latência ou ao volume necessários. Até lá, manter uma única fonte transacional reduz custo operacional e melhora a auditabilidade.
