<?php

declare(strict_types=1);

/**
 * Bootstrap do PHPUnit — barreira de segurança contra acidentes como o incidente
 * 2026-08-02 (docs/incidents/2026-08-02-reset-acidental-do-postgres-local.md).
 *
 * Roda ANTES de o Laravel carregar .env. Fixa a topologia de teste e aborta se
 * variáveis conflitantes ameaçarem um banco que não seja descartável.
 *
 * Topologias permitidas (fail-closed):
 *   - SQLite :memory:  → atalho rápido isolado (default do phpunit.xml).
 *   - PostgreSQL dev_memory_test → suíte de paridade (CI + phpunit.pgsql.xml).
 *
 * Tudo o resto (dev_memory, postgres do compose de dev, database/database.sqlite,
 * qualquer DSN arbitrário) → abort antes de RefreshDatabase ver o banco.
 */
(static function (): void {
    /** Lê variáveis relevantes; trato ausente como string vazia. */
    $get = static fn (string $k): string => ($v = getenv($k)) === false ? '' : (string) $v;

    $appEnv = $get('APP_ENV');
    $dbConn = $get('DB_CONNECTION');
    $dbDatabase = $get('DB_DATABASE');
    $dbUrl = $get('DB_URL');

    /** Lista branca das topologias de teste. */
    $isSqliteMemory = $dbConn === 'sqlite'
        && $dbDatabase === ':memory:'
        && $dbUrl === '';

    $isPgTest = $dbConn === 'pgsql'
        && $dbDatabase === 'dev_memory_test'
        && $dbUrl === '';

    if ($isSqliteMemory || $isPgTest) {
        require __DIR__.'/../vendor/autoload.php';

        return;
    }

    /** Helper de abort — sinaliza erro ao PHPUnit em vez de silenciosamente sair. */
    $abort = static function (string $msg): never {
        fwrite(STDERR, "Bootstrap PHPUnit ABORT: {$msg}\n");
        exit(1);
    };

    if ($dbUrl !== '') {
        $abort(
            'DB_URL setado — recusa para evitar schema implícito.'
            .' Defina DB_CONNECTION/DB_DATABASE em vez de DB_URL para a suíte de testes.'
        );
    }

    if ($dbConn === 'pgsql' && $dbDatabase !== 'dev_memory_test') {
        $abort(
            "DB_DATABASE=\"{$dbDatabase}\" não é o banco de teste (esperado \"dev_memory_test\")."
            .' Use `bin/dev test` (SQLite :memory:) ou'
            .' `DB_DATABASE=dev_memory_test ./vendor/bin/phpunit -c phpunit.pgsql.xml`.'
        );
    }

    if ($dbConn === 'sqlite' && $dbDatabase !== ':memory:') {
        $abort(
            "DB_DATABASE=\"{$dbDatabase}\" é SQLite em arquivo — RefreshDatabase o destruiria."
            .' Use DB_DATABASE=:memory: (default do phpunit.xml) para testes isolados.'
        );
    }

    if ($appEnv !== '' && $appEnv !== 'testing') {
        $abort(
            "APP_ENV=\"{$appEnv}\" deve ser \"testing\". Abortado antes de carregar o Laravel."
        );
    }

    $abort(
        'Configuração de teste não reconhecida.'
        ." DB_CONNECTION=\"{$dbConn}\" DB_DATABASE=\"{$dbDatabase}\"."
        .' Use `bin/dev test` (SQLite :memory:) ou'
        .' `DB_DATABASE=dev_memory_test ./vendor/bin/phpunit -c phpunit.pgsql.xml`.'
    );
})();
