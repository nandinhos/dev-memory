<?php

namespace Tests\Feature;

use App\Enums\MemoryScope;
use App\Models\ApiToken;
use App\Models\Memory;
use App\Models\User;
use App\Services\Embeddings\EmbeddingGeneratorService;
use App\Services\Search\MemorySearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpContentSerializationTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(): string
    {
        [, $plain] = ApiToken::issue(User::factory()->create(), 'serialization');

        return $plain;
    }

    private function callTool(string $token, string $name, array $arguments)
    {
        return $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson('/api/mcp', [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'tools/call',
                'params' => ['name' => $name, 'arguments' => $arguments],
            ]);
    }

    public function test_memory_search_text_stays_a_string_when_description_splits_multibyte_character(): void
    {
        $this->mock(EmbeddingGeneratorService::class)
            ->shouldReceive('generate')
            ->andReturnNull();

        Memory::factory()->create([
            'title' => 'teste utf8',
            'description' => str_repeat('a', 199).'é',
            'scope' => MemoryScope::GLOBAL,
        ]);

        $response = $this->callTool($this->tokenFor(), 'memory_search', ['query' => 'teste utf8', 'limit' => 5]);
        $response->assertOk();

        $text = $response->json('result.content.0.text');
        $this->assertIsString($text);

        $payload = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue(mb_check_encoding($payload['results'][0]['description'], 'UTF-8'));
        $this->assertStringEndsWith('...', $payload['results'][0]['description']);
    }

    public function test_memory_search_text_stays_a_string_with_non_finite_scores(): void
    {
        $memory = Memory::factory()->create([
            'title' => 'teste score',
            'description' => 'descrição com acentuação',
            'scope' => MemoryScope::GLOBAL,
        ]);

        $this->mock(MemorySearchService::class)
            ->shouldReceive('search')
            ->once()
            ->andReturn([
                'results' => [[
                    'memory' => $memory,
                    'similarity' => NAN,
                    'rank_score' => INF,
                    'rerank_score' => -INF,
                ]],
                'search_mode' => 'hybrid',
                'reranked' => true,
            ]);

        $response = $this->callTool($this->tokenFor(), 'memory_search', ['query' => 'teste score', 'limit' => 5]);
        $response->assertOk();

        $text = $response->json('result.content.0.text');
        $this->assertIsString($text);

        $payload = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(0.0, $payload['results'][0]['similarity']);
        $this->assertEquals(0.0, $payload['results'][0]['rank_score']);
        $this->assertEquals(0.0, $payload['results'][0]['rerank_score']);
    }

    public function test_memory_search_text_stays_a_string_with_invalid_utf8_stored(): void
    {
        $this->mock(EmbeddingGeneratorService::class)
            ->shouldReceive('generate')
            ->andReturnNull();

        Memory::factory()->create([
            'title' => 'teste invalido',
            'description' => "byte inválido: \xC3",
            'scope' => MemoryScope::GLOBAL,
        ]);

        $response = $this->callTool($this->tokenFor(), 'memory_search', ['query' => 'teste invalido', 'limit' => 5]);
        $response->assertOk();

        $text = $response->json('result.content.0.text');
        $this->assertIsString($text);

        $payload = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString("\u{FFFD}", $payload['results'][0]['description']);
    }
}
