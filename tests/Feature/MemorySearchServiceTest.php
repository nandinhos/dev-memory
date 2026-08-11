<?php

namespace Tests\Feature;

use App\Models\Memory;
use App\Services\Embeddings\EmbeddingGeneratorService;
use App\Services\Search\MemorySearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemorySearchServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_combines_semantic_and_lexical_results_with_rrf(): void
    {
        $both = Memory::factory()->create([
            'title' => 'Migration com conexão recusada',
            'description' => 'Falha ao conectar no banco.',
            'embedding' => [1, 0, 0],
            'embedding_model' => 'ollama/modelo-teste',
        ]);
        $semantic = Memory::factory()->create([
            'title' => 'Falha de rede no PostgreSQL',
            'description' => 'Connection refused dentro do container.',
            'embedding' => [0.9, 0.1, 0],
            'embedding_model' => 'ollama/modelo-teste',
        ]);
        $lexical = Memory::factory()->create([
            'title' => 'Migration fora de ordem',
            'description' => 'Ajustar timestamp das migrations.',
            'embedding' => [0, 1, 0],
            'embedding_model' => 'ollama/modelo-teste',
        ]);
        $wrongSpace = Memory::factory()->create([
            'title' => 'Conteúdo sem correspondência lexical',
            'description' => 'Não deve entrar por similaridade de outro modelo.',
            'embedding' => [1, 0, 0],
            'embedding_model' => 'minimax/outro-modelo',
        ]);

        $this->mock(EmbeddingGeneratorService::class)
            ->shouldReceive('generate')
            ->once()
            ->with('migration')
            ->andReturn([
                'embedding' => [1, 0, 0],
                'model' => 'ollama/modelo-teste',
                'hash' => 'hash',
            ]);

        $result = app(MemorySearchService::class)->search('migration', limit: 10);
        $ids = $result['results']->pluck('memory.id');

        $this->assertSame('hybrid', $result['search_mode']);
        $this->assertSame($both->id, $ids->first());
        $this->assertContains($semantic->id, $ids);
        $this->assertContains($lexical->id, $ids);
        $this->assertNotContains($wrongSpace->id, $ids);
        $this->assertGreaterThan(0, $result['results']->first()['rank_score']);
    }

    public function test_uses_lexical_mode_when_embedding_is_unavailable(): void
    {
        $memory = Memory::factory()->create(['title' => 'Laravel queue timeout']);

        $this->mock(EmbeddingGeneratorService::class)
            ->shouldReceive('generate')
            ->once()
            ->andReturnNull();

        $result = app(MemorySearchService::class)->search('queue');

        $this->assertSame('lexical', $result['search_mode']);
        $this->assertSame($memory->id, $result['results']->first()['memory']->id);
    }
}
