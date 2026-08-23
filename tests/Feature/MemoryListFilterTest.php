<?php

namespace Tests\Feature;

use App\Enums\DocumentationValidationStatus;
use App\Enums\MemoryScope;
use App\Livewire\MemoryList;
use App\Models\Memory;
use App\Models\User;
use App\Services\Embeddings\EmbeddingGeneratorService;
use App\Services\Search\MemorySearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MemoryListFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_filtra_memorias_pelo_veredito_do_context7(): void
    {
        Memory::factory()->create(['title' => 'Regra confirmada pela doc oficial', 'doc_validation_status' => DocumentationValidationStatus::CONFIRMED]);
        Memory::factory()->create(['title' => 'Regra que a doc contradiz', 'doc_validation_status' => DocumentationValidationStatus::CONTRADICTED]);
        Memory::factory()->create(['title' => 'Regra ainda sem checagem', 'doc_validation_status' => null]);

        // Veredito específico → só a memória correspondente.
        Livewire::test(MemoryList::class)
            ->set('docFilter', 'confirmed')
            ->assertSee('Regra confirmada pela doc oficial')
            ->assertDontSee('Regra que a doc contradiz')
            ->assertDontSee('Regra ainda sem checagem');

        Livewire::test(MemoryList::class)
            ->set('docFilter', 'contradicted')
            ->assertSee('Regra que a doc contradiz')
            ->assertDontSee('Regra confirmada pela doc oficial');
    }

    public function test_filtro_nao_verificada_traz_apenas_memorias_sem_veredito(): void
    {
        Memory::factory()->create(['title' => 'Checada e confirmada', 'doc_validation_status' => DocumentationValidationStatus::CONFIRMED]);
        Memory::factory()->create(['title' => 'Nunca passou pelo Context7', 'doc_validation_status' => null]);

        Livewire::test(MemoryList::class)
            ->set('docFilter', 'unchecked')
            ->assertSee('Nunca passou pelo Context7')
            ->assertDontSee('Checada e confirmada');
    }

    public function test_promover_memoria_nao_validada_dispara_toast_de_erro_sem_mudar_escopo(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $memory = Memory::factory()->create([
            'scope' => 'project',
            'validation_status' => 'pending',
        ]);

        Livewire::test(MemoryList::class)
            ->call('promoteMemory', $memory->id)
            ->assertDispatched('show-toast', type: 'erro');

        $this->assertSame(MemoryScope::PROJECT, $memory->fresh()->scope);
    }

    public function test_busca_vazia_usa_lista_paginada_tradicional(): void
    {
        // Mock não é necessário — sem $search, o MemorySearchService não é chamado.
        Memory::factory()->count(3)->create();

        Livewire::test(MemoryList::class)
            ->assertSet('searchMode', 'idle')
            ->assertViewHas('memories', fn ($memories) => $memories->total() === 3);
    }

    public function test_busca_textual_delega_ao_memory_search_service(): void
    {
        $relevant = Memory::factory()->create([
            'title' => 'Laravel deploy em produção',
            'description' => 'Como fazer deploy de aplicação Laravel na VPS via Forge.',
            'stack' => 'Laravel',
        ]);
        Memory::factory()->create([
            'title' => 'Receita de bolo',
            'description' => 'Receita sem relação com Laravel',
        ]);

        // Mocka o embedding para forçar modo lexical (sem chamadas HTTP).
        $this->mock(EmbeddingGeneratorService::class)
            ->shouldReceive('generate')
            ->andReturnNull();

        Livewire::test(MemoryList::class)
            ->set('search', 'Laravel deploy')
            ->assertSet('searchMode', 'lexical')
            ->assertSee('Laravel deploy em produção')
            ->assertDontSee('Receita de bolo');
    }

    public function test_busca_textual_aplica_filtro_de_tipo_sobre_os_candidatos(): void
    {
        $error = Memory::factory()->create([
            'title' => 'Laravel deploy erro de conexão',
            'description' => 'Erro ao tentar deploy por falta de conexão com o banco.',
            'type' => 'error',
        ]);
        $lesson = Memory::factory()->create([
            'title' => 'Laravel deploy lição aprendida',
            'description' => 'Lição aprendida em deploy Laravel.',
            'type' => 'lesson',
        ]);

        $this->mock(EmbeddingGeneratorService::class)
            ->shouldReceive('generate')
            ->andReturnNull();

        Livewire::test(MemoryList::class)
            ->set('search', 'Laravel deploy')
            ->set('typeFilter', 'error')
            ->assertSee('Laravel deploy erro de conexão')
            ->assertDontSee('Laravel deploy lição aprendida');
    }

    public function test_busca_via_mcp_e_via_ui_retornam_os_mesmos_resultados(): void
    {
        // Garante paridade MCP↔humano — o critério de aceite principal
        // da Onda 5: digitar "Laravel deploy" na UI retorna os mesmos
        // resultados do MCP `memory_search` (mesma ordem por RRF).
        $top = Memory::factory()->create([
            'title' => 'Laravel deploy em produção',
            'description' => 'Como fazer deploy de Laravel em produção via Forge.',
            'type' => 'best_practice',
            'stack' => 'Laravel',
        ]);
        Memory::factory()->create([
            'title' => 'Laravel deploy zero-downtime',
            'description' => 'Estratégia de deploy zero-downtime para Laravel.',
            'type' => 'lesson',
            'stack' => 'Laravel',
        ]);
        Memory::factory()->create([
            'title' => 'Receita de bolo de chocolate',
            'description' => 'Receita sem qualquer relação com Laravel.',
        ]);

        $this->mock(EmbeddingGeneratorService::class)
            ->shouldReceive('generate')
            ->andReturnNull();

        $uiResults = Livewire::test(MemoryList::class)
            ->set('search', 'Laravel deploy')
            ->viewData('memories')
            ->items();

        $mcpResults = app(MemorySearchService::class)
            ->search('Laravel deploy', [], 20)['results']
            ->pluck('memory');

        $uiIds = collect($uiResults)->pluck('id')->all();
        $mcpIds = $mcpResults->pluck('id')->all();

        // As duas primeiras posições devem bater (ordenadas por RRF).
        $this->assertSame($mcpIds[0], $uiIds[0]);
        $this->assertGreaterThanOrEqual(2, count($uiIds));
        $this->assertSame($mcpIds[1], $uiIds[1]);
        $this->assertNotContains($uiIds[0] ?? null, [
            Memory::where('title', 'Receita de bolo de chocolate')->first()->id,
        ]);
    }
}
