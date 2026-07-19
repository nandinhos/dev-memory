<?php

namespace Tests\Feature;

use App\Enums\DocumentationValidationStatus;
use App\Livewire\MemoryList;
use App\Models\Memory;
use App\Models\User;
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
}
