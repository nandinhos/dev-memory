<?php

namespace Tests\Feature;

use App\Enums\DocumentationValidationStatus;
use App\Enums\ValidationStatus;
use App\Models\Memory;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Badge da checagem no Context7 no card da listagem, AO LADO do status de curadoria.
 *
 * São dois eixos independentes e foi essa mistura que gerou a dúvida do owner: uma memória
 * pode estar "Pendente" de validação humana e já ter sido confrontada com a documentação
 * oficial — ou o contrário. O card mostrava só o primeiro.
 */
class MemoryCardDocBadgeTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_mostra_o_resultado_da_checagem_ao_lado_do_status_de_curadoria(): void
    {
        $memoria = Memory::factory()->create([
            'title' => 'Memória confirmada pela doc',
            'validation_status' => ValidationStatus::PENDING,
            'doc_validation_status' => DocumentationValidationStatus::CONFIRMED,
        ]);

        $html = view('components.neo.memory-card', ['memoria' => $memoria])->render();

        // Os DOIS eixos, lado a lado: curadoria humana e confronto com a documentação.
        $this->assertStringContainsString(ValidationStatus::PENDING->label(), $html);
        $this->assertStringContainsString('Context7 OK', $html);
    }

    public function test_inconclusivo_aparece_no_card(): void
    {
        $memoria = Memory::factory()->create([
            'validation_status' => ValidationStatus::PENDING,
            'doc_validation_status' => DocumentationValidationStatus::INCONCLUSIVE,
        ]);

        $html = view('components.neo.memory-card', ['memoria' => $memoria])->render();

        $this->assertStringContainsString('Inconclusivo', $html);
    }

    public function test_memoria_nunca_checada_nao_ganha_badge(): void
    {
        $memoria = Memory::factory()->create([
            'validation_status' => ValidationStatus::PENDING,
            'doc_validation_status' => null,
        ]);

        $html = view('components.neo.memory-card', ['memoria' => $memoria])->render();

        // Ausência do badge É a informação: a memória ainda não passou pela checagem.
        $this->assertStringNotContainsString('Context7', $html);
        $this->assertStringNotContainsString('Inconclusivo', $html);
    }

    public function test_todo_status_tem_rotulo_curto_e_cor_proprios(): void
    {
        // Guardrail: um case novo no enum não pode entrar sem o par rótulo/cor do badge,
        // senão o card renderiza vazio ou sem contraste.
        foreach (DocumentationValidationStatus::cases() as $case) {
            $this->assertNotEmpty($case->shortLabel(), $case->value);
            $this->assertNotEmpty($case->badgeClasses(), $case->value);
            $this->assertLessThanOrEqual(20, mb_strlen($case->shortLabel()), "rótulo longo demais: {$case->value}");
        }
    }
}
