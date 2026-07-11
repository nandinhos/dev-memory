<?php

namespace App\Console\Commands;

use App\Enums\CaptureStatus;
use App\Jobs\CurateCaptureJob;
use App\Models\Capture;
use Illuminate\Console\Command;

class MemoryProcessCapturesCommand extends Command
{
    protected $signature = 'memory:process-captures
                            {--sync : Processa na hora em vez de enfileirar}
                            {--limit=0 : Limitar número de captures (0 = todas)}';

    protected $description = 'Despacha o pipeline de curadoria para captures sanitizadas pendentes';

    public function handle(): int
    {
        $query = Capture::query()
            ->whereIn('status', [CaptureStatus::PENDING, CaptureStatus::SANITIZED])
            ->orderBy('created_at');

        $limit = (int) $this->option('limit');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $captures = $query->get();

        if ($captures->isEmpty()) {
            $this->info('Nenhuma capture pendente de curadoria.');

            return self::SUCCESS;
        }

        foreach ($captures as $capture) {
            $this->option('sync')
                ? CurateCaptureJob::dispatchSync($capture)
                : CurateCaptureJob::dispatch($capture);
        }

        $verb = $this->option('sync') ? 'processadas' : 'enfileiradas';
        $this->info("{$captures->count()} captures {$verb}.");

        return self::SUCCESS;
    }
}
