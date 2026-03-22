<?php

namespace App\Livewire;

use App\Models\Memory;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Memórias Técnicas')]
class MemoryList extends Component
{
    use WithPagination;

    public string $search = '';

    public ?string $typeFilter = null;

    public ?string $scopeFilter = null;

    public ?string $stackFilter = null;

    public ?string $statusFilter = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => null],
        'scopeFilter' => ['except' => null],
        'stackFilter' => ['except' => null],
        'statusFilter' => ['except' => null],
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedScopeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStackFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->typeFilter = null;
        $this->scopeFilter = null;
        $this->stackFilter = null;
        $this->statusFilter = null;
        $this->resetPage();
    }

    public function render()
    {
        $memories = Memory::query()
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('title', 'ILIKE', "%{$this->search}%")
                ->orWhere('description', 'ILIKE', "%{$this->search}%")
            ))
            ->when($this->typeFilter, fn ($q, $type) => $q->where('type', $type))
            ->when($this->scopeFilter, fn ($q, $scope) => $q->where('scope', $scope))
            ->when($this->stackFilter, fn ($q, $stack) => $q->where('stack', 'ILIKE', "%{$stack}%"))
            ->when($this->statusFilter, fn ($q, $status) => $q->where('validation_status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        $stacks = Memory::selectRaw('DISTINCT stack')
            ->whereNotNull('stack')
            ->pluck('stack')
            ->filter()
            ->sort()
            ->values();

        return view('livewire.memory-list', [
            'memories' => $memories,
            'stacks' => $stacks,
        ]);
    }
}
