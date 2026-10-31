<?php

namespace App\Livewire;

use App\Enums\KnowledgeEdgeStatus;
use App\Enums\MemoryType;
use App\Models\KnowledgeEdge;
use App\Models\Memory;
use App\Services\KnowledgeGraph\KnowledgeGraphQueryService;
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

    public ?string $selectedMemoryId = null;

    #[Url]
    public string $stack = '';

    public string $search = '';

    public int $depth = 2;

    public function mount(?string $focus = null): void
    {
        if ($focus) {
            $this->focus = $focus;
            $this->selectedMemoryId = $focus;
        }
    }

    public function setFocus(string $memoryId): void
    {
        $this->focus = $memoryId;
        $this->selectedMemoryId = $memoryId;
        $this->dispatch('graph-data-updated', data: $this->getGraphData());
    }

    public function clearFocus(): void
    {
        $this->focus = null;
        $this->dispatch('graph-data-updated', data: $this->getGraphData());
    }

    public function selectNode(string $memoryId): void
    {
        $this->selectedMemoryId = $memoryId;
    }

    public function clearSelection(): void
    {
        $this->selectedMemoryId = null;
    }

    public function updatedStack(): void
    {
        $this->dispatch('graph-data-updated', data: $this->getGraphData());
    }

    public function updatedSearch(): void
    {
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
     * Subgrafo de alta precisão focado em até 2 saltos (Ponytail Mode)
     */
    private function getFocusedGraphData(): array
    {
        $rootMemory = Memory::find($this->focus);
        if (! $rootMemory) {
            return ['nodes' => [], 'edges' => []];
        }

        $queryService = app(KnowledgeGraphQueryService::class);
        $relatedResults = $queryService->relatedTo($rootMemory, depth: $this->depth, limit: 40);

        $nodes = [];
        $edges = [];
        $addedNodes = [];

        // Adiciona nó raiz
        $nodes[] = $this->formatNode($rootMemory, isRoot: true);
        $addedNodes[$rootMemory->id] = true;

        $edgeSet = [];

        foreach ($relatedResults as $res) {
            /** @var Memory $mem */
            $mem = $res['memory'];

            if (! isset($addedNodes[$mem->id])) {
                $nodes[] = $this->formatNode($mem, isRoot: false, depth: $res['depth']);
                $addedNodes[$mem->id] = true;
            }

            foreach ($res['path'] ?? [] as $segment) {
                $edgeId = $segment['edge_id'] ?? null;
                $direction = $segment['direction'] ?? 'outgoing';
                $relation = $segment['relation'] ?? 'relates_to';

                $from = $direction === 'outgoing' ? $rootMemory->id : $mem->id;
                $to = $direction === 'outgoing' ? $mem->id : $rootMemory->id;

                $edgeKey = "{$from}->{$to}:{$relation}";

                if (! isset($edgeSet[$edgeKey])) {
                    $edgeSet[$edgeKey] = true;
                    $edges[] = $this->formatEdge($from, $to, $relation, $edgeId);
                }
            }
        }

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    /**
     * Visão geral dos clusters de conhecimento validados
     */
    private function getOverviewGraphData(): array
    {
        $edgeQuery = KnowledgeEdge::query()
            ->with(['source.memory', 'target.memory'])
            ->where('status', KnowledgeEdgeStatus::VALIDATED->value);

        if ($this->stack !== '') {
            $edgeQuery->where(function ($q) {
                $q->whereHas('source.memory', fn ($sq) => $sq->where('stack', 'like', "%{$this->stack}%"))
                    ->orWhereHas('target.memory', fn ($tq) => $tq->where('stack', 'like', "%{$this->stack}%"));
            });
        }

        $edgesRecords = $edgeQuery->latest()->limit(50)->get();

        $nodes = [];
        $edges = [];
        $addedNodes = [];

        foreach ($edgesRecords as $edge) {
            $sourceMemory = $edge->source?->memory;
            $targetMemory = $edge->target?->memory;

            if (! $sourceMemory || ! $targetMemory) {
                continue;
            }

            // Filtro por busca textual opcional
            if ($this->search !== '') {
                $needle = Str::lower($this->search);
                $matchesSource = Str::contains(Str::lower($sourceMemory->title), $needle);
                $matchesTarget = Str::contains(Str::lower($targetMemory->title), $needle);

                if (! $matchesSource && ! $matchesTarget) {
                    continue;
                }
            }

            if (! isset($addedNodes[$sourceMemory->id])) {
                $nodes[] = $this->formatNode($sourceMemory, isRoot: false);
                $addedNodes[$sourceMemory->id] = true;
            }

            if (! isset($addedNodes[$targetMemory->id])) {
                $nodes[] = $this->formatNode($targetMemory, isRoot: false);
                $addedNodes[$targetMemory->id] = true;
            }

            $edges[] = $this->formatEdge(
                $sourceMemory->id,
                $targetMemory->id,
                $edge->relation_type->value,
                $edge->id
            );
        }

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    private function formatNode(Memory $memory, bool $isRoot = false, ?int $depth = null): array
    {
        $type = $memory->type;
        $title = $memory->title;
        $shortTitle = Str::limit($title, 24);

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
            'id' => $memory->id,
            'label' => ($isRoot ? '🎯 ' : '').$shortTitle,
            'title' => $title,
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
            'type' => $type->value,
            'stack' => $memory->stack,
        ];
    }

    private function formatEdge(string $from, string $to, string $relation, ?string $edgeId = null): array
    {
        $edgeColor = match ($relation) {
            'resolves' => '#16a34a',
            'prevents' => '#0d9488',
            'causes' => '#dc2626',
            default => '#374151',
        };

        return [
            'id' => $edgeId ?? "{$from}-{$to}-{$relation}",
            'from' => $from,
            'to' => $to,
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

    public function getSelectedMemoryProperty(): ?Memory
    {
        if (! $this->selectedMemoryId) {
            return null;
        }

        return Memory::find($this->selectedMemoryId);
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
            'selectedMemory' => $this->selectedMemory,
            'rootMemory' => $this->rootMemory,
            'availableStacks' => $this->availableStacks,
        ]);
    }
}
