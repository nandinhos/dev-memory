# Incidente - Reset acidental do PostgreSQL local

**Data:** 2026-08-02  
**Impacto:** o banco `dev_memory` do Compose de desenvolvimento foi recriado; usuários e dados locais ficaram indisponíveis. A instância recuperável SQLite continha a cópia dos dados e foi restaurada. Uma verificação posterior também atingiu o mesmo caminho direto de teste e exigiu nova restauração da cópia SQLite.

## Causa raiz

Um teste com `RefreshDatabase` foi executado no container de desenvolvimento sem uma fronteira física de banco de testes. O processo Laravel alcançou `DB_DATABASE=dev_memory` e `RefreshDatabase` executou `migrate:fresh` nesse banco.

A primeira tentativa de contenção foi insuficiente: a guarda em `tests/TestCase.php` era restrita a `PostgresNativeSearchTest` e dependia de `getenv()`. Ela não cobria todos os testes que usam `RefreshDatabase` nem garantia que PHPUnit, Dotenv e a configuração Laravel observassem a mesma topologia. O caminho direto `php artisan test` permaneceu capaz de atingir o banco de desenvolvimento.

## Contenção e recuperação

1. Interromper qualquer comando de migration ou backfill.
2. Confirmar as contagens do PostgreSQL afetado em modo somente leitura.
3. Localizar `database/database.sqlite`, que preservava os dados de negócio.
4. Restaurar transacionalmente usuários, memórias, captures, execuções de curadoria, grupos, skills e perfis de harness para o PostgreSQL vazio.
5. Não restaurar API tokens; credenciais MCP antigas permanecem invalidadas e devem ser reemitidas.

## Prevenção exigida

- Não executar `RefreshDatabase` contra qualquer banco compartilhado do Compose.
- Criar um serviço PostgreSQL dedicado e descartável para `dev_memory_test`, com volume e credenciais exclusivos.
- Instalar bootstrap PHPUnit que valide e fixe toda a topologia de teste antes de o Laravel carregar o ambiente.
- Fazer `bin/dev test` ser o único caminho suportado para a suíte PostgreSQL; caminhos diretos sem a identidade de teste devem falhar fechados.
- O job de CI deve exportar explicitamente as variáveis reais do banco de teste e rodar a mesma suíte PostgreSQL isolada.

O plano executável está em [`docs/plans/plano-contencao-banco-de-testes-2026-08-02.md`](../plans/plano-contencao-banco-de-testes-2026-08-02.md).
