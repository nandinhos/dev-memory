<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Teste de regressão do bootstrap PHPUnit (tests/bootstrap.php).
 *
 * O bootstrap deve abortar a execução para qualquer topologia de banco que não
 * seja SQLite :memory: ou PostgreSQL dev_memory_test, defendendo contra o
 * incidente 2026-08-02 (docs/incidents/2026-08-02-reset-acidental-do-postgres-local.md).
 *
 * Como o bootstrap aborta (exit/saída com fatal) antes de autoload, não é possível
 * testá-lo pelo PHPUnit diretamente. Este teste cobre o contrato verificando que:
 *
 *   1. O arquivo `tests/bootstrap.php` existe e contém a lista branca esperada.
 *   2. O phpunit.xml e phpunit.pgsql.xml apontam para ele.
 *   3. `phpunit.xml` usa `force="true"` em DB_DATABASE (proteção primária).
 *
 * A validação funcional do guard fica por conta da execução manual adversarial
 * documentada em docs/plans/plano-execucao-pendencias-2026-08-11.md.
 */
final class BootstrapGuardTest extends TestCase
{
    private string $repoRoot;

    protected function setUp(): void
    {
        $this->repoRoot = dirname(__DIR__, 2);
    }

    public function test_bootstrap_file_exists(): void
    {
        $this->assertFileExists("{$this->repoRoot}/tests/bootstrap.php");
    }

    public function test_phpunit_xml_uses_bootstrap(): void
    {
        $xml = (string) file_get_contents("{$this->repoRoot}/phpunit.xml");
        $this->assertStringContainsString('bootstrap="tests/bootstrap.php"', $xml);
    }

    public function test_phpunit_pgsql_xml_uses_bootstrap(): void
    {
        $xml = (string) file_get_contents("{$this->repoRoot}/phpunit.pgsql.xml");
        $this->assertStringContainsString('bootstrap="tests/bootstrap.php"', $xml);
    }

    public function test_phpunit_xml_forces_sqlite_memory(): void
    {
        $xml = (string) file_get_contents("{$this->repoRoot}/phpunit.xml");
        $this->assertMatchesRegularExpression(
            '/<env\s+name="DB_CONNECTION"\s+value="sqlite"\s+force="true"/',
            $xml,
            'phpunit.xml deve forçar DB_CONNECTION=sqlite com force="true"',
        );
        $this->assertMatchesRegularExpression(
            '/<env\s+name="DB_DATABASE"\s+value=":memory:"\s+force="true"/',
            $xml,
            'phpunit.xml deve forçar DB_DATABASE=:memory: com force="true"',
        );
    }

    public function test_phpunit_pgsql_xml_forces_dev_memory_test(): void
    {
        $xml = (string) file_get_contents("{$this->repoRoot}/phpunit.pgsql.xml");
        $this->assertMatchesRegularExpression(
            '/<env\s+name="DB_DATABASE"\s+value="dev_memory_test"\s+force="true"/',
            $xml,
            'phpunit.pgsql.xml deve forçar DB_DATABASE=dev_memory_test com force="true"',
        );
    }

    public function test_bootstrap_whitelists_expected_topologies(): void
    {
        $bootstrap = (string) file_get_contents("{$this->repoRoot}/tests/bootstrap.php");
        $this->assertStringContainsString("':memory:'", $bootstrap, 'Bootstrap deve listar SQLite :memory:');
        $this->assertStringContainsString("'dev_memory_test'", $bootstrap, 'Bootstrap deve listar PostgreSQL dev_memory_test');
    }

    public function test_makefile_migrate_fresh_does_not_execute(): void
    {
        $makefile = (string) file_get_contents("{$this->repoRoot}/Makefile");
        $body = $this->extractTargetBody($makefile, 'migrate-fresh');
        // O corpo do target pode упомar migrate:fresh em mensagens @echo (avisos),
        // mas não deve executar `docker compose exec ... migrate:fresh` nem
        // `php artisan migrate:fresh` diretamente.
        $this->assertStringNotContainsString(
            'docker compose exec app php artisan migrate:fresh',
            $body,
            'migrate-fresh target não deve executar migrate:fresh; apenas avisar e abortar',
        );
        $this->assertStringContainsString('exit 1', $body, 'migrate-fresh target deve abortar com exit 1');
    }

    private function extractTargetBody(string $makefile, string $target): string
    {
        $lines = explode("\n", $makefile);
        $body = [];
        $inTarget = false;
        foreach ($lines as $line) {
            if (str_starts_with($line, "{$target}:")) {
                $inTarget = true;

                continue;
            }
            if ($inTarget) {
                if ($line !== '' && ! str_starts_with($line, "\t") && ! str_starts_with($line, ' ')) {
                    break;
                }
                $body[] = $line;
            }
        }

        return implode("\n", $body);
    }
}
