<?php

namespace App\Providers;

use App\Services\Curation\AnthropicCurationEngine;
use App\Services\Curation\KnowledgePreparationEngine;
use App\Services\SettingsService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(KnowledgePreparationEngine::class, AnthropicCurationEngine::class);
    }

    public function boot(SettingsService $settings): void
    {
        // Configurações do painel (DB, criptografadas) sobrepõem o env.
        $settings->applyOverrides();
    }
}
