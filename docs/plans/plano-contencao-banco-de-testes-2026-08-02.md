# Plano de contenção - banco de testes isolado

**Estado:** proposto para execução  
**Data:** 2026-08-02  
**Origem:** incidente de reset acidental de `dev_memory` documentado em [`docs/incidents/2026-08-02-reset-acidental-do-postgres-local.md`](../incidents/2026-08-02-reset-acidental-do-postgres-local.md).

## Objetivo

Permitir executar toda a suíte, migrations de teste e dados injetados pelos testes em
PostgreSQL com `pgvector`, sem que esses comandos possam usar o banco local preservado
(`dev_memory`). A proteção deve ser simultaneamente física e lógica: um erro de
configuração do PHPUnit não pode transformar o banco de desenvolvimento em alvo de
`RefreshDatabase`.

## Decisão operacional proposta

| Uso | Banco | Serviço/volume | Pode ser apagado por testes? |
|---|---|---|---|
| Aplicação local e dados do usuário | `dev_memory` | `postgres` / `dev_postgres` | Nunca |
| Testes automatizados | `dev_memory_test` | `postgres-test` / `test_postgres` | Sim, exclusivamente pelos testes |

O banco de teste será um segundo serviço PostgreSQL no Compose de desenvolvimento, com
usuário, senha, nome de container e volume próprios. Ele não terá porta publicada no host:
apenas o container `app` o acessará pela rede interna do Compose. Isso impede compartilhar
volume, processo ou credenciais com `postgres`.

Os dados criados durante um teste são deliberadamente transitórios: `RefreshDatabase` migra
o schema e cada teste usa transação. Para ensaios manuais que precisem permanecer visíveis,
o operador poderá usar `bin/dev psql-test`; a próxima execução da suíte poderá resetá-los.
O banco preservado para uso contínuo permanece `dev_memory`.

## Critérios de pronto

1. `bin/dev test` executa a suíte completa em `dev_memory_test`, não em SQLite nem em
   `dev_memory`.
2. Um `RefreshDatabase` iniciado com `DB_DATABASE=dev_memory`, host errado, ambiente fora de
   `testing` ou marcador de segurança ausente falha no bootstrap PHPUnit, antes de o Laravel
   carregar `.env.dev` e antes de `migrate:fresh`.
3. O container de teste usa `pgvector/pgvector:pg16`, aplica todas as migrations e cobre a
   busca lexical/`pgvector` real.
4. Após `bin/dev test`, os dados de `dev_memory` permanecem acessíveis e com a mesma
   contagem de referência registrada antes do comando.
5. CI executa a mesma suíte PostgreSQL isolada; SQLite, caso mantido, fica apenas como atalho
   rápido explícito e não como evidência final de entrega.
6. Os documentos e atalhos não recomendam `migrate:fresh` contra o Compose de desenvolvimento.

## Execução por etapas

### 1. Fechar as portas inseguras

- Criar `tests/bootstrap.php` e apontar os dois XMLs PHPUnit para ele. Esse bootstrap, executado
  antes de o Laravel carregar `.env.dev`, é a fonte de verdade das variáveis `DB_*` do processo
  de teste; ele falha se houver valores conflitantes.
- Remover a guarda específica de `PostgresNativeSearchTest` em `tests/TestCase.php`. Ela depende
  de `getenv()` e não cobre todos os testes com `RefreshDatabase`, portanto não é uma barreira de
  segurança válida.
- Permitir somente duas topologias:
  - SQLite em memória para o atalho rápido explícito;
  - PostgreSQL de teste com `APP_ENV=testing`, `DB_DATABASE=dev_memory_test` e o marcador
    `TEST_DATABASE_GUARD=dev-memory-test`.
- O comando seguro também define `DB_HOST=postgres-test`, credenciais de teste, cache `array`,
  sessão `array`, fila `sync` e `DB_URL` vazio. O CI usa a mesma identidade de banco, com host
  próprio do runner.
- O bootstrap deve chamar `putenv`, preencher `$_ENV` e `$_SERVER` com o mesmo conjunto
  validado. Assim o PHPUnit, o Dotenv e o Laravel não podem observar bancos diferentes no mesmo
  processo.
- Adicionar testes de regressão da própria guarda em processo isolado: configuração de
  desenvolvimento deve falhar antes de qualquer migration; a configuração de teste deve passar.

### 2. Isolar a infraestrutura física

- Em `docker-compose.dev.yml`, criar `postgres-test` sob profile `test`, com imagem
  `pgvector/pgvector:pg16`, volume nomeado `test_postgres`, credenciais exclusivas e healthcheck
  contra `dev_memory_test`.
- Não publicar porta para `postgres-test`; criar somente a resolução DNS interna
  `postgres-test` para o container `app`.
- Manter `postgres` e `dev_postgres` inalterados como o único destino da aplicação em
  `http://localhost:25080`.
- Registrar a decisão em ADR novo antes do merge, incluindo a regra de que testes nunca usam
  serviço, volume ou credencial da aplicação local.

### 3. Tornar o caminho seguro o padrão

- Evoluir `bin/dev` para subir o profile de teste quando necessário e fornecer:
  - `bin/dev test [filtros]`: suíte completa no PostgreSQL de teste;
  - `bin/dev test-fast [filtros]`: SQLite em memória, claramente rotulado como atalho;
  - `bin/dev psql-test`: inspeção manual de `dev_memory_test`.
- Atualizar `Makefile` para que `make test`, `make test-unit` e `make test-feature` chamem os
  atalhos seguros. Remover ou bloquear o alvo genérico `migrate-fresh`, que hoje aponta para o
  Compose sem sufixo.
- Ampliar `phpunit.pgsql.xml` para incluir as suítes Unit e Feature, com a mesma fonte de
  cobertura do `phpunit.xml`. O XML documenta a configuração, mas não é a barreira de segurança:
  as variáveis reais do comando e a guarda são obrigatórias.
- Fazer comandos diretos como `docker compose ... exec app php artisan test` falharem fechados
  quando herdarem `dev_memory`. O atalho `bin/dev test` será o caminho suportado.

### 4. Provar paridade e não destruição

- Rodar inicialmente a suíte inteira em PostgreSQL; corrigir apenas testes que codifiquem uma
  limitação de SQLite, sem ocultar divergências de produção.
- Manter e ampliar o gate de recursos nativos: extensão `vector`, índice GIN `tsvector`, busca
  lexical e escrita/leitura de embedding.
- Para o teste de isolamento, registrar antes e depois de `bin/dev test` as contagens de
  `users`, `memories`, `captures` e `skills` em `dev_memory`; comprovar que o teste executou em
  `dev_memory_test` pelas contagens/migrations desse banco.
- Executar no CI a suíte completa em PostgreSQL de serviço e, opcionalmente em etapa separada,
  o atalho SQLite rápido. O job não reutiliza o banco de qualquer ambiente persistente.

### 5. Corrigir os documentos operacionais

- Atualizar `docs/ambiente-local.md` com a topologia de dois bancos, comandos suportados e a
  distinção entre dado local preservado e fixture de teste descartável.
- Atualizar o incidente com a barreira generalizada e a infraestrutura física após a execução.
- Corrigir `README.md`, que hoje expõe `php artisan migrate:fresh --seed` como reset de dev, e
  tornar `bin/dev test` o comando canônico.
- Corrigir a divergência interna de `docs/STATUS.md` entre o resumo de 236 testes e a tabela de
  228 testes; a atualização usará a saída real da suíte após a mudança.

## Verificação de entrega

Executar, nesta ordem:

```bash
bin/dev up
bin/dev test
bin/dev test-fast
bin/dev pint --test
docker compose -f docker-compose.dev.yml exec -T postgres \
  psql -U dev_memory -d dev_memory -c 'select count(*) from users;'
```

Além da suíte, validar manualmente que o banco de teste responde como `dev_memory_test` e que
o banco local continua com os dados restaurados. O relatório final deve informar as contagens
antes/depois e qualquer teste SQLite que permaneça propositalmente fora do gate PostgreSQL.

## Fora de escopo

- Alterar produção, VPS, tokens MCP ou os dados atuais do usuário.
- Criar uma terceira instalação de homologação persistente. Caso surja a necessidade de preservar
  cenários manuais entre resets da suíte, ela será uma decisão separada, porque não deve competir
  com a natureza descartável do banco automatizado.
