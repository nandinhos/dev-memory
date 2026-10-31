<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ValidationStatus;
use App\Models\KnowledgeEdge;
use App\Models\KnowledgeNode;
use App\Models\Memory;
use App\Services\KnowledgeGraph\KnowledgeGraphProjector;
use App\Services\KnowledgeGraph\RelationProposer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProposeMemoryRelationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public string $memoryId,
        public int $candidateLimit = 3,
    ) {}

    public function handle(
        KnowledgeGraphProjector $projector,
        RelationProposer $proposer,
    ): void {
        $memory = Memory::find($this->memoryId);

        if ($memory === null) {
            return;
        }

        $sourceNode = $projector->projectMemory($memory);

        $candidates = $this->findCandidates($memory, $sourceNode);

        foreach ($candidates as $candidate) {
            try {
                $proposer->propose($memory, $candidate);
            } catch (\Throwable $e) {
                Log::warning('ProposeMemoryRelationsJob: Falha ao propor relação', [
                    'source_id' => $memory->id,
                    'target_id' => $candidate->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @return Collection<int, Memory>
     */
    private function findCandidates(Memory $memory, KnowledgeNode $sourceNode): Collection
    {
        // Identifica IDs de nós já conectados diretamente para evitar relações duplicadas
        $existingConnectedNodeIds = KnowledgeEdge::query()
            ->where('source_node_id', $sourceNode->id)
            ->orWhere('target_node_id', $sourceNode->id)
            ->get()
            ->map(fn (KnowledgeEdge $edge) => $edge->source_node_id === $sourceNode->id ? $edge->target_node_id : $edge->source_node_id)
            ->all();

        $connectedMemoryIds = KnowledgeNode::query()
            ->whereIn('id', $existingConnectedNodeIds)
            ->whereNotNull('memory_id')
            ->pluck('memory_id')
            ->push($memory->id)
            ->all();

        return Memory::query()
            ->whereNotIn('id', $connectedMemoryIds)
            ->where('validation_status', ValidationStatus::VALIDATED)
            ->where(function ($query) use ($memory) {
                if ($memory->project_id !== null) {
                    $query->where('project_id', $memory->project_id)
                        ->orWhere('scope', 'global');
                } else {
                    $query->where('scope', 'global');
                }
            })
            ->when($memory->stack, function ($query, $stack) {
                $terms = array_filter(array_map('trim', explode(',', str_replace('|', ',', $stack))));
                if (! empty($terms)) {
                    $query->where(function ($q) use ($terms) {
                        foreach ($terms as $term) {
                            $q->orWhere('stack', 'like', "%{$term}%");
                        }
                    });
                }
            })
            ->orderByDesc('recurrence_count')
            ->limit($this->candidateLimit)
            ->get();
    }
}
