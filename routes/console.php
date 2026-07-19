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
