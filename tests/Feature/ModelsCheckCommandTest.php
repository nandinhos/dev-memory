<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Probe de capacidade de modelos (Curadoria v2): verifica por modelo as
 * tarefas reais do motor e persiste o resultado p/ manter a fila
 * free→pago atualizada.
 */
class ModelsCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_check_marks_model_capable_when_all_tasks_pass(): void
    {
        config([
            'services.minimax.base_url' => 'https://fake.test/anthropic',
            'services.minimax.api_key' => 'k',
            'services.minimax.free_models' => ['beam-free'],
            'services.minimax.fallback_models' => [],
        ]);

        Http::fake(function ($request) {
            $content = json_encode($request->data());

            $json = str_contains($content, 'MODELS_CHECK_RELATION')
                ? '{"relation":"supports","confidence":0.9,"evidence_excerpt":"trecho"}'
                : (str_contains($content, 'MODELS_CHECK_VERDICT')
                    ? '{"verdict":"confirmed","confidence":0.9}'
                    : json_encode([
                        'title' => 'Erro de migration com coluna duplicada',
                        'summary' => 'Migration re-adicionava colunas já existentes na tabela.',
                        'problem' => 'duplicate column name ao migrar',
                        'root_cause' => null,
                        'solution' => 'guardas Schema::hasColumn',
                        'category' => 'error',
                        'technologies' => [['name' => 'Laravel', 'version' => '13']],
                        'evidence' => [],
                        'applicability' => ['migrations'],
                        'risks' => [],
                        'confidence' => 0.9,
                    ]));

            return Http::response([
                'content' => [['type' => 'text', 'text' => $json]],
                'usage' => ['input_tokens' => 10, 'output_tokens' => 20],
            ]);
        });

        $this->artisan('memory:models-check')->assertExitCode(0);

        $check = json_decode((string) Setting::get('curation.models_check'), true);
        $this->assertTrue($check['beam-free']['capable']);
        $this->assertSame('free', $check['beam-free']['tier']);
    }
}
