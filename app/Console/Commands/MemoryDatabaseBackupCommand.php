<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class MemoryDatabaseBackupCommand extends Command
{
    protected $signature = 'memory:backup-db
                            {--retention=14 : Dias de retenção de backups históricos}
                            {--output= : Diretório customizado para salvar o backup}';

    protected $description = 'Gera backup compactado do banco de dados com expiração automática por retenção';

    public function handle(): int
    {
        $connection = config('database.default', 'sqlite');
        $outputDir = $this->resolveOutputDirectory();

        if (! File::isDirectory($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        $this->info("Iniciando backup do banco de dados [driver: {$connection}]...");

        $backupFile = match ($connection) {
            'sqlite' => $this->backupSqlite($outputDir),
            'pgsql' => $this->backupPgsql($outputDir),
            default => null,
        };

        if ($backupFile === null) {
            $this->error("Driver de banco de dados [{$connection}] não é suportado para backup automático.");
            Log::error("Backup falhou: Driver [{$connection}] não suportado.");

            return self::FAILURE;
        }

        if (! File::exists($backupFile) || File::size($backupFile) === 0) {
            $this->error("Falha ao gerar o arquivo de backup em [{$backupFile}].");
            Log::error("Backup falhou: Arquivo [{$backupFile}] não gerado ou vazio.");

            return self::FAILURE;
        }

        $fileSizeKb = round(File::size($backupFile) / 1024, 2);
        $this->info("Backup gerado com sucesso: [{$backupFile}] ({$fileSizeKb} KB)");
        Log::info('Backup de banco de dados gerado com sucesso', [
            'file' => $backupFile,
            'size_kb' => $fileSizeKb,
            'connection' => $connection,
        ]);

        $retentionDays = max(1, (int) $this->option('retention'));
        $prunedCount = $this->pruneOldBackups($outputDir, $retentionDays);

        if ($prunedCount > 0) {
            $this->info("Retenção aplicada: {$prunedCount} backup(s) antigo(s) removido(s) (> {$retentionDays} dias).");
        }

        return self::SUCCESS;
    }

    private function resolveOutputDirectory(): string
    {
        $custom = $this->option('output');

        if (! empty($custom)) {
            return rtrim((string) $custom, '/');
        }

        return storage_path('app/backups');
    }

    private function backupSqlite(string $outputDir): ?string
    {
        $dbPath = config('database.connections.sqlite.database');

        if (! $dbPath || ! File::exists($dbPath)) {
            $this->warn("Arquivo SQLite [{$dbPath}] não encontrado.");

            return null;
        }

        $timestamp = now()->format('Y-m-d_His');
        $targetFile = "{$outputDir}/devmemory_sqlite_{$timestamp}.sqlite.gz";

        $content = File::get($dbPath);
        $compressed = gzencode($content, 9);

        if ($compressed === false) {
            return null;
        }

        File::put($targetFile, $compressed);

        return $targetFile;
    }

    private function backupPgsql(string $outputDir): ?string
    {
        $cfg = config('database.connections.pgsql');
        $host = (string) ($cfg['host'] ?? '127.0.0.1');
        $port = (string) ($cfg['port'] ?? '5432');
        $database = (string) ($cfg['database'] ?? 'devmemory');
        $username = (string) ($cfg['username'] ?? 'postgres');
        $password = (string) ($cfg['password'] ?? '');

        $timestamp = now()->format('Y-m-d_His');
        $targetFile = "{$outputDir}/devmemory_pgsql_{$database}_{$timestamp}.dump";

        $command = [
            'pg_dump',
            '-h', $host,
            '-p', $port,
            '-U', $username,
            '-F', 'c',
            '-b',
            '-f', $targetFile,
            $database,
        ];

        $process = Process::env(['PGPASSWORD' => $password])
            ->timeout(600)
            ->run($command);

        if (! $process->successful()) {
            $this->error('Falha na execução do pg_dump: '.$process->errorOutput());
            Log::error('pg_dump retornou erro', [
                'error' => $process->errorOutput(),
                'exit_code' => $process->exitCode(),
            ]);

            return null;
        }

        return $targetFile;
    }

    private function pruneOldBackups(string $outputDir, int $retentionDays): int
    {
        $files = File::files($outputDir);
        $threshold = now()->subDays($retentionDays)->getTimestamp();
        $pruned = 0;

        foreach ($files as $file) {
            if ($file->getMTime() < $threshold) {
                File::delete($file->getPathname());
                $pruned++;
            }
        }

        return $pruned;
    }
}
