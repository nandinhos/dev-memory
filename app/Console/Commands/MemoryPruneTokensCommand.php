<?php

namespace App\Console\Commands;

use App\Models\ApiToken;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MemoryPruneTokensCommand extends Command
{
    protected $signature = 'memory:prune-tokens
                            {--days=30 : Dias após a data de expiração para exclusão definitiva}';

    protected $description = 'Remove tokens de API expirados há mais de X dias para limpeza do banco de dados';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $threshold = now()->subDays($days);

        $query = ApiToken::whereNotNull('expires_at')
            ->where('expires_at', '<', $threshold);

        $count = $query->count();

        if ($count === 0) {
            $this->info('Nenhum token expirado elegível para limpeza.');

            return self::SUCCESS;
        }

        $query->delete();

        $this->info("Limpeza de segurança: {$count} token(s) expirado(s) há mais de {$days} dias foram removidos.");
        Log::info('Limpeza de tokens expirados concluída', [
            'pruned_tokens' => $count,
            'threshold_days' => $days,
        ]);

        return self::SUCCESS;
    }
}
