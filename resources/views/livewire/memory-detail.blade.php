<div class="animate-fade-in-up">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <a href="{{ route('memories.index') }}" wire:navigate
           class="flex items-center gap-1.5 font-mono text-sm no-underline text-black hover:text-neo-magenta transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Voltar para a lista
        </a>
        <div class="flex gap-2">
            <a href="{{ route('memories.edit', $memory) }}"
               class="btn-neo bg-neo-teal neo-border-sm shadow-neo px-4 py-2 font-heading text-sm hover:bg-neo-yellow transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Editar
            </a>
            <button wire:click="delete"
                    wire:confirm="Tem certeza que deseja excluir esta memória?"
                    class="btn-neo bg-neo-magenta neo-border-sm shadow-neo px-4 py-2 font-heading text-sm hover:bg-neo-yellow transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Excluir
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <main class="lg:col-span-2">
            <div class="card-neo bg-neo-white neo-border shadow-neo overflow-hidden relative">
                
                {{-- Header Refinement: Fundo Azul Pastel (#E0E7FF) com badge Roxo (#6366F1) --}}
                <div class="h-12 bg-[#E0E7FF] border-b-2 border-black flex items-center px-6 justify-between">
                    <span class="text-xs font-mono text-gray-500 font-bold uppercase tracking-widest">
                        MEM_ID: {{ str_pad($memory->id, 5, '0', STR_PAD_LEFT) }}
                    </span>
                    <div class="flex items-center gap-2">
                        <span class="bg-[#6366F1] text-white border-2 border-black px-3 py-1 text-xs font-black uppercase tracking-tighter">
                            {{ $memory->validation_status->label() }}
                        </span>
                        @if ($memory->doc_validation_status)
                            @php
                                $docVariant = match ($memory->doc_validation_status->value) {
                                    'confirmed' => 'bg-neo-green',
                                    'partially_confirmed' => 'bg-neo-yellow',
                                    'contradicted' => 'bg-neo-magenta text-white',
                                    default => 'bg-gray-300',
                                };
                            @endphp
                            <span class="{{ $docVariant }} border-2 border-black px-3 py-1 text-xs font-black uppercase tracking-tighter"
                                  title="Validação documental (Context7)">
                                DOC: {{ $memory->doc_validation_status->label() }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="absolute top-16 -right-3 flex flex-col items-center gap-1 z-10">
                    @if($memory->stack)
                        <span class="bg-black text-white px-3 py-1 text-xs font-bold font-heading shadow-neo rotate-2">
                            {{ $memory->stack }}
                        </span>
                    @endif
                    <span class="bg-neo-magenta border-2 border-black px-3 py-1 text-xs font-bold font-heading shadow-neo -rotate-1">
                        {{ $memory->recurrence_count }}x
                    </span>
                </div>

                <div class="p-6">
                    <div class="flex flex-wrap gap-2 mb-4">
                        <span class="inline-block {{ $memory->type->color() }} border-2 border-black px-3 py-1 text-xs font-bold font-heading">
                            {{ $memory->type->label() }}
                        </span>
                        <span class="inline-block {{ $memory->scope->badgeColor() }} border-2 border-black px-3 py-1 text-xs font-bold font-heading">
                            {{ $memory->scope->label() }}
                        </span>
                    </div>

                    @if($memory->validation_status->value === 'validated')
                        <div class="caution-scroll-container mb-6 -mx-6">
                            <div class="caution-scroll-text">
                                VALIDATED ARCHITECT /// VALIDATED ARCHITECT /// VALIDATED ARCHITECT /// VALIDATED ARCHITECT /// VALIDATED ARCHITECT ///
                            </div>
                        </div>
                    @else
                        <div class="border-b-2 border-black mb-6 opacity-10"></div>
                    @endif

                    <h1 class="font-heading text-3xl mb-6">{{ $memory->title }}</h1>

                    <x-neo.content-block :text="$memory->description" />

                    @if($memory->official_reference)
                        <div class="neo-border-sm shadow-neo-sm p-4 bg-neo-teal/20 mt-6">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                                <span class="text-xs font-bold font-mono uppercase">Referência Oficial</span>
                            </div>
                            <a href="{{ $memory->official_reference }}" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="font-mono text-sm text-neo-magenta hover:text-black underline underline-offset-2 break-all">
                                {{ $memory->official_reference }}
                            </a>
                        </div>
                    @endif
                    
                    {{-- Footer de Status Refinement --}}
                    <div class="mt-8 pt-6 border-t-2 border-black flex flex-wrap gap-6 bg-gray-50 -mx-6 px-6 py-4">
                        <div class="flex flex-col">
                            <span class="text-[10px] uppercase font-bold text-gray-400 leading-none mb-1">Status de Validação</span>
                            <span class="text-sm font-black text-neo-green uppercase">
                                {{ $memory->validation_status->label() }}
                            </span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[10px] uppercase font-bold text-gray-400 leading-none mb-1">Nível de Urgência</span>
                            <span class="text-sm font-black text-neo-magenta uppercase">
                                {{ $memory->type->value === 'error' ? 'ALTA RELEVÂNCIA' : 'NORMAL' }}
                            </span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[10px] uppercase font-bold text-gray-400 leading-none mb-1">Escopo de Aplicação</span>
                            <span class="text-sm font-black text-neo-teal uppercase">
                                {{ $memory->scope->label() }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <aside class="lg:col-span-1">
            <div class="card-neo bg-neo-white neo-border shadow-neo p-4">
                <h3 class="font-heading text-lg pb-2 border-b-2 border-black">Informações</h3>
                
                <div class="mt-4">
                    <span class="text-xs font-bold font-mono uppercase text-gray-500 block mb-1">Ocorrências</span>
                    <span class="font-heading text-3xl text-neo-magenta">{{ $memory->recurrence_count }}</span>
                </div>

                <div class="mt-4">
                    <span class="text-xs font-bold font-mono uppercase text-gray-500 block mb-1">Criado em</span>
                    <span class="font-mono text-sm">{{ $memory->created_at->format('d/m/Y \à\s H:i') }}</span>
                </div>

                <div class="mt-4">
                    <span class="text-xs font-bold font-mono uppercase text-gray-500 block mb-1">Atualizado</span>
                    <span class="font-mono text-sm">{{ $memory->updated_at->format('d/m/Y \à\s H:i') }}</span>
                </div>

                @if($memory->project_id)
                    <div class="mt-4">
                        <span class="text-xs font-bold font-mono uppercase text-gray-500 block mb-1">Projeto</span>
                        <span class="font-mono text-sm">{{ $memory->project_id }}</span>
                    </div>
                @endif
            </div>

            <div class="card-neo bg-neo-white neo-border shadow-neo p-4 mt-4">
                <h3 class="font-heading text-sm pb-2 border-b-2 border-black mb-4">Ações Rápidas</h3>
                <div class="space-y-3">
                    <button wire:click="incrementRecurrence" 
                            class="btn-neo bg-neo-green neo-border-sm shadow-neo-sm px-4 py-2 font-heading text-xs w-full hover:bg-neo-yellow transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        +1 Ocorrência
                    </button>

                    @if($memory->validation_status->value === 'pending')
                        <button wire:click="markAsValidated" 
                                class="btn-neo bg-neo-teal neo-border-sm shadow-neo-sm px-4 py-2 font-heading text-xs w-full hover:bg-neo-yellow transition-colors flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            Validar Memória
                        </button>
                    @endif

                    @if($memory->scope->value !== 'global')
                        <button wire:click="promoteToGlobal" 
                                class="w-full bg-[#60A5FA] text-black border-2 border-black px-4 py-2 font-black uppercase text-xs shadow-[4px_4px_0px_0px_#000] active:translate-x-[2px] active:translate-y-[2px] active:shadow-none transition-all flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                            Promover p/ Global
                        </button>
                    @endif
                </div>
            </div>
        </aside>
    </div>
</div>
