<?php

namespace App\Providers;

use App\Services\Curation\AnthropicCurationEngine;
use App\Services\Curation\KnowledgePreparationEngine;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(KnowledgePreparationEngine::class, AnthropicCurationEngine::class);
    }

    public function boot(): void
    {
        //
    }
}
