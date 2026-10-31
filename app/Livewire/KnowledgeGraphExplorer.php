<?php

namespace App\Livewire;

use App\Enums\KnowledgeEdgeStatus;
use App\Enums\KnowledgeNodeKind;
use App\Enums\MemoryType;
use App\Models\KnowledgeEdge;
use App\Models\KnowledgeNode;
use App\Models\Memory;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Grafo Causal')]
class KnowledgeGraphExplorer extends Component
{
    #[Url]
    public ?string $focus = null;

    public ?string $selectedNodeId = null;

    #[Url]
    public string $stack = '';

    public string $search = '';

    public int $depth = 2;

    public function mount(?string $focus = null): void
    {
        if ($focus) {
            $this->focus = $focus;
            $node = KnowledgeNode::where('memory_id', $focus)->first();
            if ($node) {
                $this->selectedNodeId = $node->id;
            }
        }
    }

    public function setFocus(string $memoryId): void
    {
        $this->focus = $memoryId;
        $node = KnowledgeNode::where('memory_id', $memoryId)->first();
        if ($node) {
            $this->selectedNodeId = $node->id;
        }
        $this->dispatch('graph-data-updated', data: $this->getGraphData());
    }

    public function clearFocus(): void
    {
        $this->focus = null;
        $this->dispatch('graph-data-updated', data: $this->getGraphData());
    }

    public function selectNode(string $nodeId): void
    {
        $this->selectedNodeId = $nodeId;
    }

    public function clearSelection(): void
    {
        $this->selectedNodeId = null;
    }

    public function updatedStack(): void
    {
        $this->focus = null;
        $this->dispatch('graph-data-updated', data: $this->getGraphData());
    }

    public function updatedSearch(): void
    {
        $this->focus = null;
        $this->dispatch('graph-data-updated', data: $this->getGraphData());
    }

    /**
     * @return array{nodes: array<int, array<string, mixed>>, edges: array<int, array<string, mixed>>}
     */
    public function getGraphData(): array
    {
        if ($this->focus) {
            return $this->getFocusedGraphData();
        }

        return $this->getOverviewGraphData();
    }

    /**
     * Subgrafo de alta precisão focado na memória e suas conexões de até 2 saltos (Ponytail Mode)
     */
    private function getFocusedGraphData(): array
    {
        $rootNode = KnowledgeNode::with('memory')->where('memory_id', $this->focus)->first();
        if (! $rootNode) {
            return ['nodes' => [], 'edges' => []];
        }

        $nodes = [];
        $edges = [];
        $addedNodes = [];
        $addedEdges = [];

        // Adiciona o nó raiz
        $nodes[] = $this->formatNode($rootNode, isRoot: true);
        $addedNodes[$rootNode->id] = true;

        // Salto 1: arestas diretas conectadas ao nó raiz
        $directEdges = KnowledgeEdge::with(['source.memory', 'target.memory'])
            ->where('status', KnowledgeEdgeStatus::VALIDATED->value)
            ->where(function ($q) use ($rootNode) {
                $q->where('source_node_id', $rootNode->id)
                    ->orWhere('target_node_id', $rootNode->id);
            })
            ->get();

        $hop1NodeIds = [];

        foreach ($directEdges as $edge) {
            $neighborNode = $edge->source_node_id === $rootNode->id ? $edge->target : $edge->source;
            if (! $neighborNode) {
                continue;
            }

            if (! isset($addedNodes[$neighborNode->id])) {
                $nodes[] = $this->formatNode($neighborNode, isRoot: false, depth: 1);
                $addedNodes[$neighborNode->id] = true;
            }

            if (! isset($addedEdges[$edge->id])) {
                $edges[] = $this->formatEdge($edge);
                $addedEdges[$edge->id] = true;
            }

            $hop1NodeIds[] = $neighborNode->id;
        }

        // Salto 2: arestas que partem dos vizinhos de salto 1 para outros nós
        if (! empty($hop1NodeIds)) {
            $hop2Edges = KnowledgeEdge::with(['source.memory', 'target.memory'])
                ->where('status', KnowledgeEdgeStatus::VALIDATED->value)
                ->where(function ($q) use ($hop1NodeIds) {
                    $q->whereIn('source_node_id', $hop1NodeIds)
                        ->orWhereIn('target_node_id', $hop1NodeIds);
                })
                ->limit(60)
                ->get();

            foreach ($hop2Edges as $edge) {
                $sourceNode = $edge->source;
                $targetNode = $edge->target;

                if (! $sourceNode || ! $targetNode) {
                    continue;
                }

                if (! isset($addedNodes[$sourceNode->id])) {
                    $nodes[] = $this->formatNode($sourceNode, isRoot: false, depth: 2);
                    $addedNodes[$sourceNode->id] = true;
                }

                if (! isset($addedNodes[$targetNode->id])) {
                    $nodes[] = $this->formatNode($targetNode, isRoot: false, depth: 2);
                    $addedNodes[$targetNode->id] = true;
                }

                if (! isset($addedEdges[$edge->id])) {
                    $edges[] = $this->formatEdge($edge);
                    $addedEdges[$edge->id] = true;
                }
            }
        }

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    /**
     * Visão geral dos clusters de conhecimento e tecnologias validadas
     */
    private function getOverviewGraphData(): array
    {
        $edgeQuery = KnowledgeEdge::query()
            ->with(['source.memory', 'target.memory'])
            ->where('status', KnowledgeEdgeStatus::VALIDATED->value);

        if ($this->stack !== '') {
            $edgeQuery->where(function ($q) {
                $q->whereHas('source.memory', fn ($sq) => $sq->where('stack', 'ilike', "%{$this->stack}%"))
                    ->orWhereHas('target.memory', fn ($tq) => $tq->where('stack', 'ilike', "%{$this->stack}%"))
                    ->orWhereHas('source', fn ($sq) => $sq->where('label', 'ilike', "%{$this->stack}%"))
                    ->orWhereHas('target', fn ($tq) => $tq->where('label', 'ilike', "%{$this->stack}%"));
            });
        }

        $edgesRecords = $edgeQuery->latest()->limit(80)->get();

        $nodes = [];
        $edges = [];
        $addedNodes = [];

        foreach ($edgesRecords as $edge) {
            $sourceNode = $edge->source;
            $targetNode = $edge->target;

            if (! $sourceNode || ! $targetNode) {
                continue;
            }

            // Filtro por busca textual opcional
            if ($this->search !== '') {
                $needle = Str::lower($this->search);
                $matchesSource = Str::contains(Str::lower($sourceNode->label), $needle);
                $matchesTarget = Str::contains(Str::lower($targetNode->label), $needle);

                if (! $matchesSource && ! $matchesTarget) {
                    continue;
                }
            }

            if (! isset($addedNodes[$sourceNode->id])) {
                $nodes[] = $this->formatNode($sourceNode, isRoot: false);
                $addedNodes[$sourceNode->id] = true;
            }

            if (! isset($addedNodes[$targetNode->id])) {
                $nodes[] = $this->formatNode($targetNode, isRoot: false);
                $addedNodes[$targetNode->id] = true;
            }

            $edges[] = $this->formatEdge($edge);
        }

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    private function formatNode(KnowledgeNode $node, bool $isRoot = false, ?int $depth = null): array
    {
        $isMemory = $node->kind === KnowledgeNodeKind::MEMORY && $node->memory !== null;
        $title = $node->label;
        $memory = $node->memory;

        if ($isMemory && $memory) {
            $type = $memory->type;
            $shortTitle = Str::limit($memory->title, 26);

            $color = match ($type) {
                MemoryType::ERROR => [
                    'background' => '#fee2e2',
                    'border' => '#ef4444',
                    'highlight' => ['background' => '#fecaca', 'border' => '#dc2626'],
                ],
                MemoryType::BEST_PRACTICE => [
                    'background' => '#dcfce7',
                    'border' => '#22c55e',
                    'highlight' => ['background' => '#bbf7d0', 'border' => '#16a34a'],
                ],
                MemoryType::LESSON => [
                    'background' => '#fef9c3',
                    'border' => '#eab308',
                    'highlight' => ['background' => '#fef08a', 'border' => '#ca8a04'],
                ],
                default => [
                    'background' => '#f3f4f6',
                    'border' => '#000000',
                    'highlight' => ['background' => '#e5e7eb', 'border' => '#000000'],
                ],
            };

            if ($isRoot) {
                $color = [
                    'background' => '#4f46e5',
                    'border' => '#000000',
                    'highlight' => ['background' => '#4338ca', 'border' => '#000000'],
                ];
            }

            $fontColor = $isRoot ? '#ffffff' : '#000000';

            return [
                'id' => $node->id,
                'label' => ($isRoot ? '🎯 ' : '').$shortTitle,
                'title' => $memory->title,
                'shape' => 'box',
                'margin' => 10,
                'borderWidth' => $isRoot ? 4 : 2,
                'borderWidthSelected' => 4,
                'color' => $color,
                'font' => [
                    'color' => $fontColor,
                    'face' => 'Space Grotesk, monospace, sans-serif',
                    'size' => $isRoot ? 14 : 12,
                    'bold' => $isRoot,
                ],
                'shadow' => [
                    'enabled' => true,
                    'color' => '#000000',
                    'size' => 4,
                    'x' => 3,
                    'y' => 3,
                ],
                'depth' => $depth,
                'isRoot' => $isRoot,
                'kind' => 'memory',
                'memoryId' => $memory->id,
                'type' => $type->value,
                'stack' => $memory->stack,
            ];
        }

        // Nó de Tecnologia ou Conceito
        return [
            'id' => $node->id,
            'label' => '⚡ '.$title,
            'title' => 'Tecnologia / Hub: '.$title,
            'shape' => 'ellipse',
            'margin' => 12,
            'borderWidth' => 3,
            'borderWidthSelected' => 5,
            'color' => [
                'background' => '#ccfbf1', // neo-teal pastel
                'border' => '#0d9488',
                'highlight' => ['background' => '#99f6e4', 'border' => '#0f766e'],
            ],
            'font' => [
                'color' => '#134e4a',
                'face' => 'Space Grotesk, monospace, sans-serif',
                'size' => 13,
                'bold' => true,
            ],
            'shadow' => [
                'enabled' => true,
                'color' => '#000000',
                'size' => 4,
                'x' => 3,
                'y' => 3,
            ],
            'depth' => $depth,
            'isRoot' => false,
            'kind' => 'technology',
            'memoryId' => null,
            'type' => 'technology',
            'stack' => null,
        ];
    }

    private function formatEdge(KnowledgeEdge $edge): array
    {
        $relation = $edge->relation_type->value;
        $edgeColor = match ($relation) {
            'resolves' => '#16a34a',
            'prevents' => '#0d9488',
            'causes' => '#dc2626',
            'applies_to' => '#0284c7',
            default => '#374151',
        };

        return [
            'id' => $edge->id,
            'from' => $edge->source_node_id,
            'to' => $edge->target_node_id,
            'label' => $relation,
            'arrows' => 'to',
            'color' => [
                'color' => $edgeColor,
                'highlight' => '#000000',
                'hover' => '#000000',
            ],
            'font' => [
                'color' => '#000000',
                'face' => 'monospace',
                'size' => 10,
                'background' => '#ffffff',
                'strokeWidth' => 1,
                'strokeColor' => '#000000',
                'align' => 'middle',
            ],
            'width' => 2,
            'smooth' => [
                'type' => 'curvedCW',
                'roundness' => 0.15,
            ],
        ];
    }

    public function getSelectedNodeProperty(): ?KnowledgeNode
    {
        if (! $this->selectedNodeId) {
            return null;
        }

        return KnowledgeNode::with(['memory', 'incomingEdges.source.memory'])->find($this->selectedNodeId);
    }

    public function getRootMemoryProperty(): ?Memory
    {
        if (! $this->focus) {
            return null;
        }

        return Memory::find($this->focus);
    }

    public function getAvailableStacksProperty(): Collection
    {
        return Memory::whereNotNull('stack')
            ->where('stack', '!=', '')
            ->distinct()
            ->pluck('stack')
            ->flatMap(fn ($s) => array_map('trim', explode(',', $s)))
            ->unique()
            ->filter()
            ->sort()
            ->values();
    }

    public function render()
    {
        return view('livewire.knowledge-graph-explorer', [
            'graphData' => $this->getGraphData(),
            'selectedNode' => $this->selectedNode,
            'rootMemory' => $this->rootMemory,
            'availableStacks' => $this->availableStacks,
        ]);
    }
}
