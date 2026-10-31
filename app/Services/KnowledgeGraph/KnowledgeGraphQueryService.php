<?php

namespace App\Services\KnowledgeGraph;

use App\Enums\KnowledgeEdgeStatus;
use App\Enums\MemoryScope;
use App\Models\KnowledgeEdge;
use App\Models\KnowledgeNode;
use App\Models\Memory;
use Illuminate\Support\Str;

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

    /**
     * Renderiza o grafo causal em formato Mermaid (graph LR)
     */
    public function renderMermaid(Memory $root, array $results, string $direction = 'LR'): string
    {
        $lines = ["graph {$direction}"];
        $rootId = 'node_'.substr(md5($root->id), 0, 8);
        $cleanRootTitle = Str::limit(str_replace(['"', "\n", "\r"], ["'", ' ', ' '], $root->title), 50);
        $rootEmoji = match ($root->type->value) {
            'error' => '❌ ',
            'best_practice' => '⭐ ',
            'lesson' => '💡 ',
            default => '📄 ',
        };
        $lines[] = "    {$rootId}[\"{$rootEmoji}{$cleanRootTitle}\"]:::root";

        $renderedEdges = [];

        foreach ($results as $result) {
            /** @var Memory|null $mem */
            $mem = $result['memory'] ?? null;
            if ($mem === null) {
                continue;
            }

            $memId = 'node_'.substr(md5($mem->id), 0, 8);
            $cleanMemTitle = Str::limit(str_replace(['"', "\n", "\r"], ["'", ' ', ' '], $mem->title), 45);
            $typeEmoji = match ($mem->type->value) {
                'error' => '❌ ',
                'best_practice' => '⭐ ',
                'lesson' => '💡 ',
                default => '📄 ',
            };
            $typeClass = match ($mem->type->value) {
                'error' => ':::typeError',
                'best_practice' => ':::typeBp',
                'lesson' => ':::typeLesson',
                default => '',
            };
            $lines[] = "    {$memId}[\"{$typeEmoji}{$cleanMemTitle}\"]{$typeClass}";

            foreach ($result['path'] ?? [] as $segment) {
                $relation = $segment['relation'] ?? 'relates_to';
                $edgeDirection = $segment['direction'] ?? 'outgoing';
                $edgeKey = $edgeDirection === 'incoming' ? "{$memId}->{$rootId}:{$relation}" : "{$rootId}->{$memId}:{$relation}";

                if (! isset($renderedEdges[$edgeKey])) {
                    $renderedEdges[$edgeKey] = true;
                    if ($edgeDirection === 'incoming') {
                        $lines[] = "    {$memId} -->|{$relation}| {$rootId}";
                    } else {
                        $lines[] = "    {$rootId} -->|{$relation}| {$memId}";
                    }
                }
            }
        }

        $lines[] = '    classDef root fill:#4f46e5,stroke:#312e81,stroke-width:3px,color:#fff;';
        $lines[] = '    classDef typeError fill:#fee2e2,stroke:#ef4444,stroke-width:2px,color:#7f1d1d;';
        $lines[] = '    classDef typeBp fill:#dcfce7,stroke:#22c55e,stroke-width:2px,color:#14532d;';
        $lines[] = '    classDef typeLesson fill:#fef9c3,stroke:#eab308,stroke-width:2px,color:#713f12;';

        return implode("\n", $lines);
    }
}
