<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class MemoryDatabaseBackupCommandTest extends TestCase
{
    private string $tempBackupDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempBackupDir = storage_path('framework/testing/test_backups_'.uniqid());
        File::makeDirectory($this->tempBackupDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->tempBackupDir)) {
            File::deleteDirectory($this->tempBackupDir);
        }
        parent::tearDown();
    }

    public function test_it_backs_up_sqlite_database_successfully(): void
    {
        $dummyDb = $this->tempBackupDir.'/dummy.sqlite';
        File::put($dummyDb, 'SQLite format 3 -- dummy content');

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $dummyDb,
        ]);

        $this->artisan('memory:backup-db', ['--output' => $this->tempBackupDir])
            ->expectsOutputToContain('Backup gerado com sucesso')
            ->assertSuccessful();

        $files = File::glob($this->tempBackupDir.'/*.sqlite.gz');
        $this->assertCount(1, $files);
        $this->assertGreaterThan(0, File::size($files[0]));
    }

    public function test_it_backs_up_pgsql_database_using_process(): void
    {
        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql' => [
                'driver' => 'pgsql',
                'host' => '127.0.0.1',
                'port' => '5432',
                'database' => 'test_memory',
                'username' => 'test_user',
                'password' => 'secret',
            ],
        ]);

        Process::fake(function ($process) {
            $cmd = is_array($process->command) ? implode(' ', $process->command) : $process->command;
            if (str_contains($cmd, 'pg_dump')) {
                // Localiza o caminho de saída nos argumentos
                if (is_array($process->command)) {
                    $key = array_search('-f', $process->command, true);
                    if ($key !== false && isset($process->command[$key + 1])) {
                        File::put($process->command[$key + 1], 'FAKE PG_DUMP DATA');
                    }
                }

                return Process::result('pg_dump success', 0);
            }

            return Process::result('', 0);
        });

        $this->artisan('memory:backup-db', ['--output' => $this->tempBackupDir])
            ->expectsOutputToContain('Backup gerado com sucesso')
            ->assertSuccessful();

        $dumpFiles = File::glob($this->tempBackupDir.'/*.dump');
        $this->assertCount(1, $dumpFiles);
    }

    public function test_it_prunes_backups_older_than_retention_days(): void
    {
        $dummyDb = $this->tempBackupDir.'/dummy.sqlite';
        File::put($dummyDb, 'SQLite format 3 -- test');

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $dummyDb,
        ]);

        // Cria arquivo simulado antigo (15 dias atrás)
        $oldFile = $this->tempBackupDir.'/devmemory_sqlite_old.sqlite.gz';
        File::put($oldFile, 'old backup');
        touch($oldFile, now()->subDays(15)->getTimestamp());

        $this->assertTrue(File::exists($oldFile));

        $this->artisan('memory:backup-db', [
            '--output' => $this->tempBackupDir,
            '--retention' => 14,
        ])
            ->expectsOutputToContain('Retenção aplicada: 1 backup(s) antigo(s) removido(s)')
            ->assertSuccessful();

        $this->assertFalse(File::exists($oldFile));
    }

    public function test_it_fails_when_pg_dump_returns_error(): void
    {
        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql' => [
                'driver' => 'pgsql',
                'host' => '127.0.0.1',
                'port' => '5432',
                'database' => 'test_memory',
                'username' => 'test_user',
                'password' => 'secret',
            ],
        ]);

        Process::fake(function ($process) {
            $cmd = is_array($process->command) ? implode(' ', $process->command) : $process->command;
            if (str_contains($cmd, 'pg_dump')) {
                return Process::result(
                    errorOutput: 'pg_dump: connection to database failed',
                    exitCode: 1
                );
            }

            return Process::result('', 0);
        });

        $this->artisan('memory:backup-db', ['--output' => $this->tempBackupDir])
            ->expectsOutputToContain('Falha na execução do pg_dump')
            ->assertFailed();
    }
}
