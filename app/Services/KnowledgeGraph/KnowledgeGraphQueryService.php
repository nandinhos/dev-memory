<?php

namespace App\Services\KnowledgeGraph;

use App\Enums\KnowledgeEdgeStatus;
use App\Enums\MemoryScope;
use App\Models\KnowledgeEdge;
use App\Models\KnowledgeNode;
use App\Models\Memory;

class KnowledgeGraphQueryService
{
    public function relatedTo(Memory $memory, int $depth = 2, int $limit = 20): array
    {
        $depth = min(max($depth, 1), 3);
        $limit = min(max($limit, 1), 100);
        $root = KnowledgeNode::where('memory_id', $memory->id)->first();

        if ($root === null) {
            return [];
        }

        $frontier = [$root->id => []];
        $visited = [$root->id => true];
        $results = [];

        for ($level = 1; $level <= $depth && $frontier !== []; $level++) {
            $frontierIds = array_keys($frontier);
            $edges = KnowledgeEdge::query()
                ->with(['source.memory', 'target.memory', 'evidence'])
                ->where('status', KnowledgeEdgeStatus::VALIDATED->value)
                ->where(function ($query) use ($frontierIds) {
                    $query->whereIn('source_node_id', $frontierIds)
                        ->orWhereIn('target_node_id', $frontierIds);
                })
                ->limit(500)
                ->get();

            $nextFrontier = [];

            foreach ($frontier as $nodeId => $path) {
                foreach ($edges as $edge) {
                    $outgoing = $edge->source_node_id === $nodeId;
                    $incoming = $edge->target_node_id === $nodeId;

                    if (! $outgoing && ! $incoming) {
                        continue;
                    }

                    $nextNode = $outgoing ? $edge->target : $edge->source;

                    if (isset($visited[$nextNode->id])) {
                        continue;
                    }

                    if ($nextNode->memory !== null && ! $this->canExpose($memory, $nextNode->memory)) {
                        $visited[$nextNode->id] = true;

                        continue;
                    }

                    $nextPath = [...$path, $this->pathSegment($edge, $outgoing)];
                    $visited[$nextNode->id] = true;
                    $nextFrontier[$nextNode->id] = $nextPath;

                    if ($nextNode->memory !== null && ! $nextNode->memory->is($memory)) {
                        $results[$nextNode->memory->id] = [
                            'memory' => $nextNode->memory,
                            'depth' => $level,
                            'path' => $nextPath,
                        ];
                    }
                }
            }

            $frontier = $nextFrontier;
        }

        return collect($results)
            ->sort(function (array $left, array $right) {
                return [$left['depth'], -$left['memory']->recurrence_count]
                    <=> [$right['depth'], -$right['memory']->recurrence_count];
            })
            ->take($limit)
            ->values()
            ->all();
    }

    private function canExpose(Memory $source, Memory $target): bool
    {
        if ($target->scope === MemoryScope::GLOBAL) {
            return true;
        }

        if ($source->scope === MemoryScope::GLOBAL) {
            return false;
        }

        return $source->project_id !== null && $source->project_id === $target->project_id;
    }

    private function pathSegment(KnowledgeEdge $edge, bool $outgoing): array
    {
        return [
            'edge_id' => $edge->id,
            'relation' => $edge->relation_type->value,
            'direction' => $outgoing ? 'outgoing' : 'incoming',
            'source' => ['id' => $edge->source->id, 'label' => $edge->source->label],
            'target' => ['id' => $edge->target->id, 'label' => $edge->target->label],
            'confidence' => $edge->confidence,
            'evidence' => $edge->evidence->map(fn ($evidence) => [
                'source_type' => $evidence->source_type,
                'memory_id' => $evidence->memory_id,
                'source_uri' => $evidence->source_uri,
                'excerpt' => $evidence->excerpt,
                'confidence' => $evidence->confidence,
            ])->values()->all(),
        ];
    }
}
