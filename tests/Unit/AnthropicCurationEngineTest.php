<?php

namespace Tests\Unit;

use App\Services\Curation\AnthropicCurationEngine;
use App\Services\Curation\CurationFailedException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AnthropicCurationEngineTest extends TestCase
{
    private function engine(): AnthropicCurationEngine
    {
        return new AnthropicCurationEngine(
            baseUrl: 'https://fake.minimax.test/anthropic',
            apiKey: 'test-key',
            model: 'MiniMax-M2.5',
        );
    }

    private function apiResponse(string $text): array
    {
        return [
            'content' => [['type' => 'text', 'text' => $text]],
            'usage' => ['input_tokens' => 100, 'output_tokens' => 200],
        ];
    }

    private function validDraftJson(): string
    {
        return json_encode([
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
        ]);
    }

    public function test_returns_draft_on_valid_response(): void
    {
        Http::fake(['*' => Http::response($this->apiResponse($this->validDraftJson()))]);

        $engine = $this->engine();
        $draft = $engine->prepare('captura de teste');

        $this->assertSame('error', $draft->category);
        $this->assertSame(1, $engine->lastAttempts);
        $this->assertSame(200, $engine->lastUsage['output_tokens']);
    }

    public function test_falls_back_to_next_model_when_first_fails_with_402(): void
    {
        Http::fake(function ($request) {
            if (($request->data()['model'] ?? null) === 'beam-free') {
                return Http::response(['error' => 'payment required'], 402);
            }

            return Http::response($this->apiResponse($this->validDraftJson()));
        });

        $engine = new AnthropicCurationEngine(
            baseUrl: 'https://fake.minimax.test/anthropic',
            apiKey: 'test-key',
            model: 'beam-free',
            models: ['beam-free', 'minimax-m3'],
        );

        $draft = $engine->prepare('captura de teste');

        $this->assertSame('error', $draft->category);
        $this->assertSame('minimax-m3', $engine->lastModel);
    }

    public function test_sends_opencode_session_and_user_agent_headers(): void
    {
        Http::fake(['*' => Http::response($this->apiResponse($this->validDraftJson()))]);

        $this->engine()->prepare('captura de teste');

        // OpenCode Go recusa sem sessão estável (HTTP 400 MissingSessionID,
        // provado em prod 2026-10-31); header extra é inofensivo p/ demais
        // endpoints Anthropic-compatible.
        Http::assertSent(fn ($request) => $request->hasHeader('x-opencode-session')
            && $request->header('x-opencode-session')[0] !== ''
            && str_contains($request->header('User-Agent')[0] ?? '', 'devmemory-hub'));
    }

    public function test_strips_code_fences_from_response(): void
    {
        Http::fake([
            '*' => Http::response($this->apiResponse("```json\n".$this->validDraftJson()."\n```")),
        ]);

        $draft = $this->engine()->prepare('captura de teste');

        $this->assertSame('error', $draft->category);
    }

    public function test_repairs_invalid_response_on_second_attempt(): void
    {
        Http::fake([
            '*' => Http::sequence()
                ->push($this->apiResponse('não sou JSON'))
                ->push($this->apiResponse($this->validDraftJson())),
        ]);

        $engine = $this->engine();
        $draft = $engine->prepare('captura de teste');

        $this->assertSame('error', $draft->category);
        $this->assertSame(2, $engine->lastAttempts);
    }

    public function test_fails_after_max_attempts(): void
    {
        Http::fake(['*' => Http::response($this->apiResponse('nunca serei JSON'))]);

        $this->expectException(CurationFailedException::class);
        $this->expectExceptionMessageMatches('/processing_failed/');

        $this->engine()->prepare('captura de teste');
    }

    public function test_throws_on_http_failure(): void
    {
        Http::fake(['*' => Http::response(['error' => 'unauthorized'], 401)]);

        $this->expectException(CurationFailedException::class);
        $this->expectExceptionMessageMatches('/HTTP 401/');

        $this->engine()->prepare('captura de teste');
    }
}
