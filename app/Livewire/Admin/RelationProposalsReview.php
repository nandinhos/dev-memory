<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\KnowledgeEdgeStatus;
use App\Models\KnowledgeEdge;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Propostas de Relação')]
class RelationProposalsReview extends Component
{
    use WithPagination;

    public function approve(string $id): void
    {
        $edge = KnowledgeEdge::with('source.memory', 'target.memory')
            ->where('status', KnowledgeEdgeStatus::PROPOSED)
            ->findOrFail($id);

        $edge->update(['status' => KnowledgeEdgeStatus::VALIDATED]);

        $this->dispatch(
            'show-toast',
            message: 'Relação validada e inserida no knowledge graph',
            type: 'sucesso',
        );
    }

    public function reject(string $id): void
    {
        $edge = KnowledgeEdge::with('source.memory', 'target.memory')
            ->where('status', KnowledgeEdgeStatus::PROPOSED)
            ->findOrFail($id);

        $edge->update(['status' => KnowledgeEdgeStatus::REJECTED]);

        $this->dispatch(
            'show-toast',
            message: 'Proposta de relação rejeitada',
            type: 'aviso',
        );
    }

    public function render()
    {
        $proposals = KnowledgeEdge::with(['source.memory', 'target.memory', 'evidence'])
            ->where('status', KnowledgeEdgeStatus::PROPOSED)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.admin.relation-proposals-review', [
            'proposals' => $proposals,
        ]);
    }
}
