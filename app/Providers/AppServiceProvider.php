<?php

namespace App\Providers;

use App\Http\Middleware\EnsureAdmin;
use App\Services\Curation\AnthropicCurationEngine;
use App\Services\Curation\KnowledgePreparationEngine;
use App\Services\SettingsService;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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

        // RBAC das telas admin também nas atualizações de componente Livewire,
        // não só no load da página (o middleware da rota é reaplicado).
        Livewire::addPersistentMiddleware([EnsureAdmin::class]);
    }
}
