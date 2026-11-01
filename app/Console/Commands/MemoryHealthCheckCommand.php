<?php

namespace App\Console\Commands;

use App\Services\Embeddings\EmbeddingGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MemoryHealthCheckCommand extends Command
{
    protected $signature = 'memory:health-check';

    protected $description = 'Verifica ativamente a integridade e latência dos serviços críticos (Embeddings e Banco)';

    public function handle(EmbeddingGeneratorService $embeddingGenerator): int
    {
        $this->info('Iniciando health check do DevMemory Hub...');

        $report = $embeddingGenerator->healthCheck();
        $isHealthy = $report['status'] === 'healthy';

        if ($isHealthy) {
            $this->info("[HEALTHY] Provedor de Embeddings [{$report['space']}] ativo (latência: {$report['latency_ms']}ms).");
            Log::info('DevMemory Healthcheck: Provedor de embeddings saudável', $report);

            return self::SUCCESS;
        }

        $this->error("[DEGRADED] Provedor de Embeddings [{$report['space']}] falhou: {$report['error']}");
        Log::critical('DevMemory Healthcheck: Degradação em embeddings detectada', $report);

        return self::FAILURE;
    }
}
