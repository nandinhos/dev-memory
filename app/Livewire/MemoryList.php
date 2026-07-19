<?php

namespace App\Livewire;

use App\Enums\MemoryScope;
use App\Models\Memory;
use Livewire\Attributes\On;
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

    public ?string $docFilter = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => null],
        'scopeFilter' => ['except' => null],
        'stackFilter' => ['except' => null],
        'statusFilter' => ['except' => null],
        'docFilter' => ['except' => null],
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

    public function updatedDocFilter(): void
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
        $this->docFilter = null;
        $this->resetPage();
    }

    #[On('promote-memory')]
    public function promoteMemory(string $id): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);

        $memory = Memory::findOrFail($id);
        $memory->update(['scope' => MemoryScope::GLOBAL]);

        $this->dispatch('show-toast', message: 'Memória promovida a Global!', type: 'sucesso');
    }

    public function render()
    {
        $memories = Memory::query()
            ->when($this->search, function ($q) {
                $term = strtolower($this->search);
                $q->where(fn ($q) => $q
                    ->whereRaw('LOWER(title) LIKE ?', ["%{$term}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$term}%"])
                );
            })
            ->when($this->typeFilter, fn ($q, $type) => $q->where('type', $type))
            ->when($this->scopeFilter, fn ($q, $scope) => $q->where('scope', $scope))
            ->when($this->stackFilter, function ($q, $stack) {
                $q->whereRaw('LOWER(stack) LIKE ?', ['%'.strtolower($stack).'%']);
            })
            ->when($this->statusFilter, fn ($q, $status) => $q->where('validation_status', $status))
            ->when($this->docFilter, function ($q, $doc) {
                // 'unchecked' = memórias que ainda não passaram pela checagem Context7.
                $doc === 'unchecked'
                    ? $q->whereNull('doc_validation_status')
                    : $q->where('doc_validation_status', $doc);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        $stacks = Memory::select('stack')
            ->distinct()
            ->whereNotNull('stack')
            ->orderBy('stack')
            ->pluck('stack')
            ->filter()
            ->values();

        return view('livewire.memory-list', [
            'memories' => $memories,
            'stacks' => $stacks,
        ]);
    }
}
