<?php

namespace Tests\Unit;

use App\Jobs\GenerateMemoryEmbeddingJob;
use App\Models\Memory;
use App\Services\Embeddings\EmbeddingGeneratorService;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

class EmbeddingGeneratorServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.embeddings.provider' => 'ollama',
            'services.embeddings.dimensions' => 3,
            'services.embeddings.ollama.host' => 'http://ollama.test',
            'services.embeddings.ollama.model' => 'modelo-teste',
        ]);
    }

    public function test_generates_embedding_in_the_configured_space(): void
    {
        Http::fake([
            'http://ollama.test/api/embeddings' => Http::response([
                'embedding' => [1, 2, 3],
            ]),
        ]);

        $service = new EmbeddingGeneratorService;
        $result = $service->generate('Memória de teste');

        $this->assertSame('ollama/modelo-teste', $result['model']);
        $this->assertSame([1.0, 2.0, 3.0], $result['embedding']);
        $this->assertSame($service->contentHash('Memória de teste'), $result['hash']);
        Http::assertSentCount(1);
    }

    public function test_rejects_vector_with_incompatible_dimensions(): void
    {
        Http::fake([
            'http://ollama.test/api/embeddings' => Http::response([
                'embedding' => [1, 2],
            ]),
        ]);

        $this->assertNull((new EmbeddingGeneratorService)->generate('Dimensão inválida'));
    }

    public function test_hash_changes_when_the_embedding_space_changes(): void
    {
        $service = new EmbeddingGeneratorService;
        $first = $service->contentHash('Mesmo conteúdo');

        config(['services.embeddings.ollama.model' => 'outro-modelo']);

        $this->assertNotSame($first, $service->contentHash('Mesmo conteúdo'));
    }

    public function test_fails_closed_for_unknown_provider(): void
    {
        config(['services.embeddings.provider' => 'desconhecido']);

        $this->expectException(InvalidArgumentException::class);

        (new EmbeddingGeneratorService)->generate('Teste');
    }

    public function test_generates_embedding_via_minimax_only_when_provider_reports_success(): void
    {
        config([
            'services.embeddings.provider' => 'minimax',
            'services.embeddings.dimensions' => 3,
            'services.embeddings.minimax.base_url' => 'https://api.minimax.test/v1',
            'services.embeddings.minimax.model' => 'embo-01',
            'services.minimax.api_key' => 'chave-de-teste',
        ]);

        Http::fake([
            'https://api.minimax.test/v1/embeddings' => Http::response([
                'vectors' => [[1, 2, 3]],
                'base_resp' => ['status_code' => 0, 'status_msg' => 'success'],
            ]),
        ]);

        $result = (new EmbeddingGeneratorService)->generate('Memória de teste');

        $this->assertSame('minimax/embo-01', $result['model']);
        $this->assertSame([1.0, 2.0, 3.0], $result['embedding']);
        Http::assertSent(fn ($request) => $request['type'] === 'db');
    }

    public function test_rejects_http_success_when_minimax_reports_provider_error(): void
    {
        config([
            'services.embeddings.provider' => 'minimax',
            'services.embeddings.dimensions' => 3,
            'services.embeddings.minimax.base_url' => 'https://api.minimax.test/v1',
            'services.embeddings.minimax.model' => 'embo-01',
            'services.minimax.api_key' => 'chave-de-teste',
        ]);

        Http::fake([
            'https://api.minimax.test/v1/embeddings' => Http::response([
                'vectors' => [],
                'base_resp' => ['status_code' => 1002, 'status_msg' => 'rate limit exceeded'],
            ]),
        ]);

        $this->assertNull((new EmbeddingGeneratorService)->generate('Memória de teste'));
    }

    public function test_embedding_job_keeps_memory_writable_when_no_vector_is_generated(): void
    {
        $memory = new Memory([
            'title' => 'Memória de teste',
            'description' => 'Descrição',
            'stack' => 'Laravel',
        ]);

        $generator = $this->createMock(EmbeddingGeneratorService::class);
        $generator->method('space')->willReturn('minimax/embo-01');
        $generator->method('contentHash')->willReturn('hash');
        $generator->method('generate')->willReturn(null);

        (new GenerateMemoryEmbeddingJob($memory))->handle($generator);

        $this->assertNull($memory->embedding);
    }
}
