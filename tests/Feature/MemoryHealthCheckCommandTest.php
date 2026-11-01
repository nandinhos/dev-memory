<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MemoryHealthCheckCommandTest extends TestCase
{
    public function test_it_reports_healthy_when_ollama_responds_with_valid_vector(): void
    {
        config([
            'services.embeddings.provider' => 'ollama',
            'services.embeddings.ollama.host' => 'http://127.0.0.1:11434',
            'services.embeddings.ollama.model' => 'modelo-teste',
            'services.embeddings.dimensions' => 3,
        ]);

        Http::fake([
            '127.0.0.1:11434/api/embeddings' => Http::response([
                'embedding' => [0.1, 0.2, 0.3],
            ], 200),
        ]);

        $this->artisan('memory:health-check')
            ->expectsOutputToContain('[HEALTHY] Provedor de Embeddings [ollama/modelo-teste]')
            ->assertSuccessful();
    }

    public function test_it_reports_degraded_when_ollama_fails_or_times_out(): void
    {
        config([
            'services.embeddings.provider' => 'ollama',
            'services.embeddings.ollama.host' => 'http://127.0.0.1:11434',
            'services.embeddings.ollama.model' => 'modelo-teste',
            'services.embeddings.dimensions' => 3,
        ]);

        Http::fake([
            '127.0.0.1:11434/api/embeddings' => Http::response([
                'error' => 'model not found',
            ], 500),
        ]);

        $this->artisan('memory:health-check')
            ->expectsOutputToContain('[DEGRADED]')
            ->assertFailed();
    }

    public function test_it_reports_degraded_when_dimensions_are_incompatible(): void
    {
        config([
            'services.embeddings.provider' => 'ollama',
            'services.embeddings.ollama.host' => 'http://127.0.0.1:11434',
            'services.embeddings.ollama.model' => 'modelo-teste',
            'services.embeddings.dimensions' => 5,
        ]);

        Http::fake([
            '127.0.0.1:11434/api/embeddings' => Http::response([
                'embedding' => [0.1, 0.2], // apenas 2 dimensões, esperado 5
            ], 200),
        ]);

        $this->artisan('memory:health-check')
            ->expectsOutputToContain('[DEGRADED]')
            ->assertFailed();
    }
}
