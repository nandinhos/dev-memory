<?php

namespace App\Livewire;

use App\Models\Memory;
use App\Services\MemoryService;
use App\Services\Search\MemorySearchService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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

    public ?string $maturityFilter = null;

    /**
     * Modo de busca reportado pelo MemorySearchService no último render
     * com `$search` preenchido: 'hybrid' | 'semantic' | 'lexical' | 'filter'.
     * 'idle' quando a busca textual está vazia (lista paginada tradicional).
     * Renderizado como badge no header da view para dar paridade MCP↔humano.
     */
    public string $searchMode = 'idle';

    protected $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => null],
        'scopeFilter' => ['except' => null],
        'stackFilter' => ['except' => null],
        'statusFilter' => ['except' => null],
        'docFilter' => ['except' => null],
        'maturityFilter' => ['except' => null],
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
        $this->maturityFilter = null;
        $this->searchMode = 'idle';
        $this->resetPage();
    }

    #[On('promote-memory')]
    public function promoteMemory(string $id): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);

        $memory = Memory::findOrFail($id);

        try {
            app(MemoryService::class)->promoteToGlobal($memory);
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('show-toast', message: $e->getMessage(), type: 'erro');

            return;
        }

        $this->dispatch('show-toast', message: 'Memória promovida a Global!', type: 'sucesso');
    }

    public function render()
    {
        $stacks = Memory::select('stack')
            ->distinct()
            ->whereNotNull('stack')
            ->orderBy('stack')
            ->pluck('stack')
            ->filter()
            ->values();

        if (trim($this->search) !== '') {
            $memories = $this->renderHybridSearch();

            return view('livewire.memory-list', [
                'memories' => $memories,
                'stacks' => $stacks,
            ]);
        }

        $this->searchMode = 'idle';

        $memories = Memory::query()
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
            ->when($this->maturityFilter, fn ($q, $mat) => $q->where('maturity', $mat))
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('livewire.memory-list', [
            'memories' => $memories,
            'stacks' => $stacks,
        ]);
    }

    /**
     * Onda 5 (2026-08-22) — delega a busca textual para o
     * MemorySearchService (lexical+semantic+RRF). Os filtros do escopo
     * (type/scope/stack) são entregues ao serviço; os filtros de ciclo
     * de vida (status/doc/maturity) são aplicados depois porque o
     * serviço atual não os cobre no nível de query. Saída é uma
     * LengthAwarePaginator com a mesma interface da lista tradicional,
     * para a view continuar usando $memories->hasPages() etc.
     */
    private function renderHybridSearch(): LengthAwarePaginator
    {
        $filters = array_filter([
            'type' => $this->typeFilter,
            'scope' => $this->scopeFilter,
            'stack' => $this->stackFilter,
        ], fn ($value) => $value !== null && $value !== '');

        // Limite alto o suficiente para a paginação da UI (12 por página)
        // não truncar resultados relevantes; o search aplica seu próprio
        // candidateLimit sobre esse N internamente.
        $searchLimit = max(120, 12 * 10);

        $result = app(MemorySearchService::class)->search(
            $this->search,
            $filters,
            $searchLimit,
        );

        $this->searchMode = $result['search_mode'];

        $memories = collect($result['results'])
            ->pluck('memory')
            ->when($this->statusFilter, fn (Collection $c) => $c->filter(
                fn (Memory $m) => $m->validation_status?->value === $this->statusFilter
            ))
            ->when($this->docFilter, function (Collection $c) {
                return $this->docFilter === 'unchecked'
                    ? $c->filter(fn (Memory $m) => $m->doc_validation_status === null)
                    : $c->filter(fn (Memory $m) => $m->doc_validation_status?->value === $this->docFilter);
            })
            ->when($this->maturityFilter, fn (Collection $c) => $c->filter(
                fn (Memory $m) => $m->maturity?->value === $this->maturityFilter
            ))
            ->values();

        $page = max(1, (int) ($this->page ?? request()->query('page', 1)));
        $perPage = 12;
        $slice = $memories->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            items: $slice,
            total: $memories->count(),
            perPage: $perPage,
            currentPage: $page,
            options: [
                'path' => request()->url(),
                'pageName' => 'page',
                'query' => request()->query(),
            ],
        );
    }
}
