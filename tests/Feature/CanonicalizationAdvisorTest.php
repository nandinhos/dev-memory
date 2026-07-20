<?php

namespace Tests\Feature;

use App\Enums\DocumentationValidationStatus;
use App\Enums\ValidationStatus;
use App\Jobs\ValidateMemoryDocumentationJob;
use App\Livewire\MemoryDetail;
use App\Models\Memory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class CanonicalizationAdvisorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.minimax.base_url' => 'https://fake.minimax.test/anthropic',
            'services.minimax.api_key' => 'test-key',
            'services.minimax.model' => 'MiniMax-M2.5',
        ]);

        $this->actingAs(User::factory()->create()); // is_admin=true por padrão
    }

    private function engineResponse(array $draft): array
    {
        return [
            'content' => [['type' => 'text', 'text' => json_encode($draft)]],
            'usage' => ['input_tokens' => 100, 'output_tokens' => 50],
        ];
    }

    private function contradictedMemory(): Memory
    {
        return Memory::factory()->create([
            'title' => 'TDD com testes obrigatórios em todo PR',
            'stack' => 'TDD',
            'validation_status' => ValidationStatus::PENDING,
            'doc_validation_status' => DocumentationValidationStatus::CONTRADICTED,
            'doc_validation_report' => [
                'library' => '/tdd-guard/docs',
                'verdict' => ['claims' => [
                    ['claim' => 'TDD obrigatório em PR', 'verdict' => 'unsupported', 'notes' => 'doc é do TDD Guard (CLI), não da metodologia'],
                ]],
                'sources' => ['https://example.com/tdd-guard'],
            ],
        ]);
    }

    public function test_falso_negativo_recomenda_manter(): void
    {
        Http::fake(['*' => Http::response($this->engineResponse([
            'assessment' => 'false_negative',
            'reasoning' => 'Context7 resolveu TDD Guard, não a metodologia TDD.',
            'recommendation' => 'keep',
            'suggested_title' => null,
            'suggested_description' => null,
            'confidence' => 0.92,
        ]))]);

        Livewire::test(MemoryDetail::class, ['memory' => $this->contradictedMemory()])
            ->call('analyzeContradiction')
            ->assertSet('canonAssessment.assessment', 'false_negative')
            ->assertSet('canonAssessment.recommendation', 'keep');
    }

    public function test_analise_nao_roda_para_memoria_nao_contradita(): void
    {
        // Guard: sem HTTP fake — se chamasse o motor, o teste falharia.
        $memory = Memory::factory()->create([
            'doc_validation_status' => DocumentationValidationStatus::PARTIALLY_CONFIRMED,
        ]);

        Livewire::test(MemoryDetail::class, ['memory' => $memory])
            ->call('analyzeContradiction')
            ->assertSet('canonAssessment', []);
    }

    public function test_manter_valida_a_memoria(): void
    {
        $memory = $this->contradictedMemory();

        Livewire::test(MemoryDetail::class, ['memory' => $memory])
            ->call('keepAsIs')
            ->assertDispatched('show-toast');

        $this->assertSame(ValidationStatus::VALIDATED, $memory->fresh()->validation_status);
    }

    public function test_aplicar_correcao_atualiza_e_revalida(): void
    {
        Queue::fake();
        $memory = $this->contradictedMemory();

        Livewire::test(MemoryDetail::class, ['memory' => $memory])
            ->set('canonAssessment', [
                'assessment' => 'real_contradiction',
                'reasoning' => 'A doc oficial contradiz o uso.',
                'recommendation' => 'correct',
                'suggested_title' => 'Título canônico corrigido',
                'suggested_description' => 'Descrição alinhada à documentação oficial',
                'confidence' => 0.8,
            ])
            ->call('applyCorrection')
            ->assertDispatched('show-toast');

        $memory->refresh();
        $this->assertSame('Título canônico corrigido', $memory->title);
        $this->assertSame('IA-assistida (canonização)', $memory->validated_by);
        Queue::assertPushed(ValidateMemoryDocumentationJob::class);
    }

    public function test_aplicar_correcao_ignora_recomendacao_que_nao_e_correct(): void
    {
        $memory = $this->contradictedMemory();
        $tituloOriginal = $memory->title;

        Livewire::test(MemoryDetail::class, ['memory' => $memory])
            ->set('canonAssessment', ['recommendation' => 'keep'])
            ->call('applyCorrection');

        $this->assertSame($tituloOriginal, $memory->fresh()->title); // nada mudou
    }

    public function test_rejeitar_marca_rejeitada(): void
    {
        $memory = $this->contradictedMemory();

        Livewire::test(MemoryDetail::class, ['memory' => $memory])
            ->call('rejectMemory');

        $this->assertSame(ValidationStatus::REJECTED, $memory->fresh()->validation_status);
    }

    public function test_reanalise_usa_a_biblioteca_sugerida_preserva_historico_e_nao_auto_valida(): void
    {
        config([
            'services.context7.base_url' => 'https://context7.test/api/v1',
            'services.context7.api_key' => null,
        ]);

        Http::fake([
            // Context7 resolve para a lib CORRETA sugerida pela IA + devolve trechos.
            'context7.test/api/v1/search*' => Http::response(['results' => [['id' => '/laravel/docs']]]),
            'context7.test/api/v1/laravel/docs*' => Http::response(
                '### Validação\nFormRequest concentra regras de validação.\nSource: https://laravel.com/docs/validation'
            ),
            // O motor devolve o veredito ancorado nos trechos.
            'fake.minimax.test/*' => Http::response($this->engineResponse([
                'status' => 'confirmed',
                'claims' => [['claim' => 'FormRequest valida', 'verdict' => 'supported', 'notes' => null]],
                'version_constraints' => [],
                'confidence' => 0.9,
            ])),
        ]);

        $memory = $this->contradictedMemory();

        Livewire::test(MemoryDetail::class, ['memory' => $memory])
            // Simula o assessment já feito, com a biblioteca correta apontada pela IA.
            ->set('canonAssessment', [
                'assessment' => 'false_negative',
                'recommendation' => 'keep',
                'suggested_context7_query' => 'laravel/framework',
            ])
            ->call('reanalyzeInContext7')
            ->assertSet('reanalysisResult.status', 'confirmed');

        $memory->refresh();
        $this->assertTrue($memory->reanalyzed_by_ai);
        $this->assertSame(DocumentationValidationStatus::CONFIRMED, $memory->doc_validation_status);
        // Trilha de auditoria: o resultado original foi preservado.
        $this->assertArrayHasKey('previous_report', $memory->doc_validation_report);
        $this->assertSame('/tdd-guard/docs', $memory->doc_validation_report['previous_report']['library']);
        // Escolha do usuário: reanálise guiada por IA NUNCA auto-valida.
        $this->assertSame(ValidationStatus::PENDING, $memory->validation_status);
    }

    public function test_reanalise_nao_repete_se_ja_reanalisada(): void
    {
        $memory = $this->contradictedMemory();
        $memory->update(['reanalyzed_by_ai' => true]);

        // Sem HTTP fake: se tentasse validar de novo, o teste falharia.
        Livewire::test(MemoryDetail::class, ['memory' => $memory])
            ->set('canonAssessment', ['suggested_context7_query' => 'laravel/framework'])
            ->call('reanalyzeInContext7')
            ->assertDispatched('show-toast', type: 'aviso');
    }
}
