<div class="animate-fade-in-up">
    <x-neo.page-header titulo="MEMÓRIAS" subtitulo="Repositório de lições aprendidas">
        <x-slot:actions>
            <a href="{{ route('memories.create') }}" class="btn-neo bg-neo-teal neo-border-sm shadow-neo px-4 py-2 font-heading text-sm hover:bg-neo-yellow transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Nova Memória
            </a>
        </x-slot:actions>
    </x-neo.page-header>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <aside class="lg:col-span-1">
            <div class="card-neo bg-neo-white neo-border shadow-neo p-4 sticky top-4">
                <h3 class="font-heading text-lg mb-4 pb-2 border-b-2 border-black">Filtros</h3>
                
                <div class="mb-4">
                    <label class="block text-xs font-bold font-mono uppercase tracking-wider mb-2">Buscar</label>
                    <div class="relative">
                        <input type="text" 
                               wire:model.live.debounce.300ms="search"
                               placeholder="Buscar memórias..."
                               class="input-neo w-full neo-border-sm shadow-neo-sm px-3 py-2 outline-none font-mono text-sm pr-8">
                        <svg class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold font-mono uppercase tracking-wider mb-2">Tipo</label>
                    <select wire:model.live="typeFilter" class="input-neo w-full neo-border-sm shadow-neo-sm px-3 py-2 outline-none font-mono text-sm cursor-pointer">
                        <option value="">Todos</option>
                        <option value="error">Erro</option>
                        <option value="lesson">Lição</option>
                        <option value="best_practice">Boa Prática</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold font-mono uppercase tracking-wider mb-2">Escopo</label>
                    <select wire:model.live="scopeFilter" class="input-neo w-full neo-border-sm shadow-neo-sm px-3 py-2 outline-none font-mono text-sm cursor-pointer">
                        <option value="">Todos</option>
                        <option value="project">Projeto</option>
                        <option value="global">Global</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold font-mono uppercase tracking-wider mb-2">Status</label>
                    <select wire:model.live="statusFilter" class="input-neo w-full neo-border-sm shadow-neo-sm px-3 py-2 outline-none font-mono text-sm cursor-pointer">
                        <option value="">Todos</option>
                        <option value="pending">Pendente</option>
                        <option value="validated">Validado</option>
                        <option value="rejected">Rejeitado</option>
                    </select>
                </div>

                @if($search || $typeFilter || $scopeFilter || $statusFilter)
                    <button wire:click="clearFilters" class="btn-neo bg-neo-salmon neo-border-sm shadow-neo-sm px-4 py-2 font-heading text-xs w-full hover:bg-neo-magenta transition-colors">
                        Limpar Filtros
                    </button>
                @endif
            </div>

            <div class="card-neo bg-neo-white neo-border shadow-neo p-4 mt-4">
                <h3 class="font-heading text-sm mb-2 pb-2 border-b-2 border-black">Estatísticas</h3>
                <div class="space-y-2 text-xs font-mono">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total:</span>
                        <span class="font-bold">{{ $memories->total() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 bg-neo-magenta border border-black"></span>
                            Erros:
                        </span>
                        <span class="font-bold">{{ $memories->where('type', 'error')->count() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 bg-neo-yellow border border-black"></span>
                            Lições:
                        </span>
                        <span class="font-bold">{{ $memories->where('type', 'lesson')->count() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 bg-neo-green border border-black"></span>
                            Boas Práticas:
                        </span>
                        <span class="font-bold">{{ $memories->where('type', 'best_practice')->count() }}</span>
                    </div>
                </div>
            </div>
        </aside>

        <main class="lg:col-span-3">
            @if($memories->isEmpty())
                <x-neo.empty-state titulo="Nenhuma memória encontrada" mensagem="Comece adicionando sua primeira memória técnica!">
                    <x-slot:actions>
                        <a href="{{ route('memories.create') }}" class="btn-neo bg-neo-teal neo-border-sm shadow-neo px-4 py-2 font-heading text-sm hover:bg-neo-yellow">
                            + Nova Memória
                        </a>
                    </x-slot:actions>
                </x-neo.empty-state>
            @else
                <div class="space-y-4">
                    @foreach($memories as $memory)
                        <div class="card-stagger-item"
                             style="animation-delay: {{ min($loop->index, 6) * 80 }}ms">
                            <x-neo.memory-card :memoria="$memory" />
                        </div>
                    @endforeach
                </div>

                @if($memories->hasPages())
                    <div class="mt-8 flex justify-center">
                        <nav class="flex gap-2 flex-wrap justify-center">
                            @if($memories->onFirstPage())
                                <span class="neo-border-sm shadow-neo-sm px-3 py-1 font-heading text-sm bg-gray-200 cursor-not-allowed opacity-50">«</span>
                            @else
                                <a href="{{ $memories->previousPageUrl() }}" class="btn-neo bg-neo-white neo-border-sm shadow-neo-sm px-3 py-1 font-heading text-sm hover:bg-neo-yellow">«</a>
                            @endif

                            @foreach($memories->getUrlRange(max(1, $memories->currentPage() - 2), min($memories->lastPage(), $memories->currentPage() + 2)) as $page => $url)
                                @if($page == $memories->currentPage())
                                    <span class="neo-border-sm shadow-neo-sm px-3 py-1 font-heading text-sm bg-neo-teal">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="btn-neo bg-neo-white neo-border-sm shadow-neo-sm px-3 py-1 font-heading text-sm hover:bg-neo-yellow">{{ $page }}</a>
                                @endif
                            @endforeach

                            @if($memories->hasMorePages())
                                <a href="{{ $memories->nextPageUrl() }}" class="btn-neo bg-neo-white neo-border-sm shadow-neo-sm px-3 py-1 font-heading text-sm hover:bg-neo-yellow">»</a>
                            @else
                                <span class="neo-border-sm shadow-neo-sm px-3 py-1 font-heading text-sm bg-gray-200 cursor-not-allowed opacity-50">»</span>
                            @endif
                        </nav>
                    </div>
                @endif
            @endif
        </main>
    </div>
</div>
