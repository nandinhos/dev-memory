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
            'validation_status' => \App\Enums\ValidationStatus::PENDING,
            'doc_validation_status' => null,
            'recurrence_count' => 0,
            'stack' => null,
        ]);
        $semantic = Memory::factory()->create([
            'title' => 'Falha de rede no PostgreSQL',
            'description' => 'Connection refused dentro do container.',
            'embedding' => [0.9, 0.1, 0],
            'embedding_model' => 'ollama/modelo-teste',
            'validation_status' => \App\Enums\ValidationStatus::PENDING,
            'doc_validation_status' => null,
            'recurrence_count' => 0,
            'stack' => null,
        ]);
        $lexical = Memory::factory()->create([
            'title' => 'Migration fora de ordem',
            'description' => 'Ajustar timestamp das migrations.',
            'embedding' => [0, 1, 0],
            'embedding_model' => 'ollama/modelo-teste',
            'validation_status' => \App\Enums\ValidationStatus::PENDING,
            'doc_validation_status' => null,
            'recurrence_count' => 0,
            'stack' => null,
        ]);
        $wrongSpace = Memory::factory()->create([
            'title' => 'Conteúdo sem correspondência lexical',
            'description' => 'Não deve entrar por similaridade de outro modelo.',
            'embedding' => [1, 0, 0],
            'embedding_model' => 'minimax/outro-modelo',
            'validation_status' => \App\Enums\ValidationStatus::PENDING,
            'doc_validation_status' => null,
            'recurrence_count' => 0,
            'stack' => null,
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

    public function test_reranks_results_favoring_validated_and_recurrent_memories(): void
    {
        // $pendingMem tem match léxico mais exato no título -> mais alto no ranking RRF inicial
        $pendingMem = Memory::factory()->create([
            'title' => 'Docker network connection timeout',
            'description' => 'Container sem acesso à rede externa.',
            'validation_status' => \App\Enums\ValidationStatus::PENDING,
            'recurrence_count' => 0,
        ]);

        // $validatedMem tem match no description -> mais baixo no ranking RRF inicial, mas validada e recorrente
        $validatedMem = Memory::factory()->create([
            'title' => 'Solução de rede isolada',
            'description' => 'Docker network connection timeout resolvido ajustando MTU.',
            'validation_status' => \App\Enums\ValidationStatus::VALIDATED,
            'doc_validation_status' => \App\Enums\DocumentationValidationStatus::CONFIRMED,
            'recurrence_count' => 5,
        ]);

        $this->mock(EmbeddingGeneratorService::class)
            ->shouldReceive('generate')
            ->andReturnNull();

        $resultReranked = app(MemorySearchService::class)->search('Docker network connection timeout', limit: 10, rerank: true);
        $resultPureRrf = app(MemorySearchService::class)->search('Docker network connection timeout', limit: 10, rerank: false);

        $this->assertTrue($resultReranked['reranked']);
        $this->assertFalse($resultPureRrf['reranked']);

        // Com rerank ativo, a memória validada e recorrente sobe para o 1º lugar
        $this->assertSame($validatedMem->id, $resultReranked['results']->first()['memory']->id);
        $this->assertArrayHasKey('rerank_score', $resultReranked['results']->first());
        $this->assertGreaterThan(0.0, $resultReranked['results']->first()['rerank_score']);
        $this->assertLessThanOrEqual(1.0, $resultReranked['results']->first()['rerank_score']);

        // Com rerank desligado, a ordem RRF léxica pura coloca o match de título em 1º
        $this->assertSame($pendingMem->id, $resultPureRrf['results']->first()['memory']->id);
    }
}
