<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-cura: reprocessa captures que falharam a curadoria (motor fora do ar ou
// timeout em conteúdo denso). O raw_content é preservado; a captura volta a
// SANITIZED e é re-enfileirada. Requer o scheduler ativo na VPS
// (cron `php artisan schedule:run` a cada minuto).
Schedule::command('memory:process-captures --retry-failed')
    ->hourly()
    ->withoutOverlapping();

// Backup diário automatizado do banco de dados (GAP 2 de Homologação)
// com retenção automática de 14 dias.
Schedule::command('memory:backup-db --retention=14')
    ->dailyAt('02:00')
    ->withoutOverlapping();

// Telemetria e monitoramento ativo do motor de IA/Embeddings (GAP 3 de Homologação)
Schedule::command('memory:health-check')
    ->everyThirtyMinutes()
    ->withoutOverlapping();

// Governança e descarte seguro de tokens expirados (GAP 4 de Homologação)
Schedule::command('memory:prune-tokens --days=30')
    ->weekly()
    ->withoutOverlapping();

// Curadoria v2: re-prova semanal dos modelos da cadeia (free são
// "limited time" e somem sem aviso; o motor reordena pelo resultado).
Schedule::command('memory:models-check')
    ->weeklyOn(1, '06:00')
    ->withoutOverlapping();
