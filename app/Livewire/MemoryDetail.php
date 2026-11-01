<?php

namespace App\Livewire;

use App\Enums\DocumentationValidationStatus;
use App\Enums\KnowledgeEdgeStatus;
use App\Enums\KnowledgeRelationType;
use App\Enums\ValidationStatus;
use App\Jobs\ValidateMemoryDocumentationJob;
use App\Models\KnowledgeEdge;
use App\Models\Memory;
use App\Services\Curation\CanonicalizationAdvisor;
use App\Services\Curation\CurationFailedException;
use App\Services\Curation\DocumentationValidator;
use App\Services\Curation\DocValidationOutcome;
use App\Services\KnowledgeGraph\KnowledgeGraphQueryService;
use App\Services\MemoryService;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Detalhes da Memória')]
class MemoryDetail extends Component
{
    public Memory $memory;

    /** Resultado efêmero da análise de contradição (IA) — não persistido. */
    public array $canonAssessment = [];

    /** Resumo efêmero da última reanálise no Context7 (para exibir o antes/depois). */
    public array $reanalysisResult = [];

    public function mount(Memory $memory): void
    {
        $this->memory = $memory;
    }

    public function delete(): void
    {
        $this->authorizeAdmin();
        app(MemoryService::class)->delete($this->memory);
        session()->flash('success', 'Memória removida com sucesso!');
        $this->redirect('/memories', navigate: true);
    }

    public function incrementRecurrence(): void
    {
        app(MemoryService::class)->incrementRecurrence($this->memory);
        $this->memory->refresh();
        $this->dispatch('show-toast',
            message: '+1 ocorrência registrada',
            type: 'sucesso'
        );
    }

    public function markAsValidated(): void
    {
        $this->authorizeAdmin();
        app(MemoryService::class)->validate($this->memory);
        $this->memory->refresh();
        $this->dispatch('show-toast',
            message: 'Memória validada com sucesso!',
            type: 'sucesso'
        );
    }

    public function promoteToGlobal(): void
    {
        $this->authorizeAdmin();

        try {
            app(MemoryService::class)->promoteToGlobal($this->memory);
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('show-toast', message: $e->getMessage(), type: 'erro');

            return;
        }

        $this->memory->refresh();
        $this->dispatch('show-toast',
            message: 'Memória promovida a Global!',
            type: 'sucesso'
        );
    }

    /**
     * Análise assistida por IA de uma memória CONTRADITA: distingue contradição real
     * de falso-negativo/erro de categoria e, se real, sugere correção. O resultado é
     * efêmero — o humano decide (Aplicar/Manter/Rejeitar).
     */
    public function analyzeContradiction(CanonicalizationAdvisor $advisor): void
    {
        $this->authorizeAdmin();

        if (! in_array($this->memory->doc_validation_status, [
            DocumentationValidationStatus::CONTRADICTED,
            DocumentationValidationStatus::INCONCLUSIVE,
        ], true)) {
            return;
        }

        try {
            $this->canonAssessment = $advisor->assess($this->memory)->toArray();
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('show-toast', message: 'Falha na análise: '.mb_substr($e->getMessage(), 0, 120), type: 'erro');
        }
    }

    /**
     * Reanálise guiada por IA: re-roda a validação documental usando a biblioteca
     * CORRETA que a IA apontou (o falso-negativo veio de resolução errada). Preserva
     * o resultado original (trilha de auditoria) e NUNCA auto-valida — a IA escolheu
     * onde olhar, então a validação continua sendo decisão humana.
     */
    public function reanalyzeInContext7(DocumentationValidator $validator): void
    {
        $this->authorizeAdmin();

        $query = $this->canonAssessment['suggested_context7_query'] ?? null;

        if (! is_string($query) || trim($query) === '') {
            return;
        }

        if ($this->memory->reanalyzed_by_ai) {
            $this->dispatch('show-toast', message: 'Esta memória já foi reanalisada uma vez.', type: 'aviso');

            return;
        }

        $previousReport = $this->memory->doc_validation_report ?? [];
        $previousStatus = $this->memory->doc_validation_status?->value;

        try {
            $outcome = $validator->validate($this->memory, $query);
        } catch (CurationFailedException $e) {
            $outcome = DocValidationOutcome::inconclusive('falha do motor na reanálise: '.$e->getMessage());
        }

        $report = $outcome->toReport();
        // Trilha de auditoria: o resultado original É a confiabilidade — nunca sobrescreve.
        $report['reanalysis'] = ['query' => $query, 'previous_library' => $previousReport['library'] ?? null, 'previous_status' => $previousStatus];
        $report['previous_report'] = $previousReport;

        app(MemoryService::class)->update($this->memory, [
            'doc_validation_status' => $outcome->status,
            'doc_validation_report' => $report,
            'reanalyzed_by_ai' => true,
            'doc_validated_at' => now(),
        ]);
        // NUNCA auto-valida (escolha do usuário): a IA escolheu a biblioteca, o humano confirma.

        $this->memory->refresh();
        $this->reanalysisResult = [
            'status' => $outcome->status->value,
            'library' => $outcome->libraryId,
            'previous_library' => $previousReport['library'] ?? null,
        ];

        $this->dispatch('show-toast', message: 'Reanálise concluída no Context7', type: 'sucesso');
    }

    public function applyCorrection(): void
    {
        $this->authorizeAdmin();

        if (($this->canonAssessment['recommendation'] ?? null) !== 'correct') {
            return;
        }

        app(MemoryService::class)->update($this->memory, [
            'title' => $this->canonAssessment['suggested_title'],
            'description' => $this->canonAssessment['suggested_description'],
            'validated_by' => 'IA-assistida (canonização)',
        ]);

        // Revalida contra a documentação para confirmar que a correção agora alinha.
        ValidateMemoryDocumentationJob::dispatch($this->memory->refresh());

        $this->canonAssessment = [];
        $this->dispatch('show-toast', message: 'Correção aplicada — revalidação documental enfileirada', type: 'sucesso');
    }

    public function keepAsIs(): void
    {
        $this->authorizeAdmin();

        // Falso-negativo / não-documentável: o humano confirma a memória sobre o Context7.
        app(MemoryService::class)->update($this->memory, [
            'validation_status' => ValidationStatus::VALIDATED,
            'validated_by' => 'humano (revisão sobre Context7)',
        ]);
        $this->memory->refresh();

        $this->canonAssessment = [];
        $this->dispatch('show-toast', message: 'Memória mantida e validada', type: 'sucesso');
    }

    public function rejectMemory(): void
    {
        $this->authorizeAdmin();

        app(MemoryService::class)->reject($this->memory);
        $this->memory->refresh();

        $this->canonAssessment = [];
        $this->dispatch('show-toast', message: 'Memória rejeitada', type: 'aviso');
    }

    public function approveRelation(string $edgeId): void
    {
        $this->authorizeAdmin();

        $edge = KnowledgeEdge::where('status', KnowledgeEdgeStatus::PROPOSED)->findOrFail($edgeId);
        $edge->update(['status' => KnowledgeEdgeStatus::VALIDATED]);

        // Curadoria v2 §3: supersedes confirmado deprecia a memória antiga
        // (estado existente `superseded`), sem deleção física.
        if ($edge->relation_type === KnowledgeRelationType::SUPERSEDES) {
            $oldMemory = $edge->target?->memory;

            if ($oldMemory !== null && $oldMemory->validation_status !== ValidationStatus::SUPERSEDED) {
                app(MemoryService::class)->supersede($oldMemory);
            }
        }

        $this->dispatch('show-toast', message: 'Relação validada no grafo', type: 'sucesso');
    }

    public function rejectRelation(string $edgeId): void
    {
        $this->authorizeAdmin();

        $edge = KnowledgeEdge::where('status', KnowledgeEdgeStatus::PROPOSED)->findOrFail($edgeId);
        $edge->update(['status' => KnowledgeEdgeStatus::REJECTED]);

        $this->dispatch('show-toast', message: 'Relação proposta rejeitada', type: 'aviso');
    }

    public function render()
    {
        $queryService = app(KnowledgeGraphQueryService::class);
        $relatedResults = $queryService->relatedTo($this->memory, depth: 2, limit: 12);

        $nodeId = $this->memory->knowledgeNode?->id;
        $proposedEdges = $nodeId ? KnowledgeEdge::with(['source.memory', 'target.memory', 'evidence'])
            ->where('status', KnowledgeEdgeStatus::PROPOSED)
            ->where(function ($q) use ($nodeId) {
                $q->where('source_node_id', $nodeId)->orWhere('target_node_id', $nodeId);
            })
            ->get() : collect();

        return view('livewire.memory-detail', [
            'relatedResults' => $relatedResults,
            'proposedEdges' => $proposedEdges,
        ]);
    }

    /** Ações de curadoria (validar/promover/excluir) são restritas a admin. */
    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);
    }
}
