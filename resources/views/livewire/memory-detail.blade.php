<div class="animate-fade-in-up">
    @php
        // Cor semântica por status: pendente = LARANJA (pede ação); validado = verde.
        $statusStyles = [
            'pending'    => ['badge' => 'bg-neo-orange text-black', 'header' => 'bg-[#FDBA74]', 'headerText' => 'text-black'],
            'validated'  => ['badge' => 'bg-neo-green text-black',  'header' => 'bg-[#E0E7FF]', 'headerText' => 'text-gray-500'],
            'rejected'   => ['badge' => 'bg-gray-400 text-black',   'header' => 'bg-[#E5E7EB]', 'headerText' => 'text-gray-500'],
            'superseded' => ['badge' => 'bg-gray-400 text-black',   'header' => 'bg-[#E5E7EB]', 'headerText' => 'text-gray-500'],
        ];
        $ss = $statusStyles[$memory->validation_status->value] ?? $statusStyles['rejected'];
    @endphp
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
                
                {{-- Header: fundo semântico (laranja p/ pendente = pede atenção) --}}
                <div class="h-12 {{ $ss['header'] }} border-b-2 border-black flex items-center px-6 justify-between">
                    <span class="text-xs font-mono {{ $ss['headerText'] }} font-bold uppercase tracking-widest">
                        MEM_ID: {{ str_pad($memory->id, 5, '0', STR_PAD_LEFT) }}
                    </span>
                    <div class="flex items-center gap-2">
                        <span class="{{ $ss['badge'] }} border-2 border-black px-3 py-1 text-xs font-black uppercase tracking-tighter">
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

                    @if($memory->doc_validation_report)
                        @php
                            $report = $memory->doc_validation_report;
                            $verdict = $report['verdict'] ?? [];
                            $docStatus = $memory->doc_validation_status?->value;
                            $docBadge = match ($docStatus) {
                                'confirmed' => 'bg-neo-green',
                                'partially_confirmed' => 'bg-neo-yellow',
                                'contradicted' => 'bg-neo-magenta text-white',
                                default => 'bg-gray-300',
                            };
                            $library = $report['library'] ?? null;
                            $libraryUrl = $library ? 'https://context7.com'.$library : null;
                            $sources = collect($report['sources'] ?? [])
                                ->map(fn ($s) => is_string($s) ? $s : ($s['url'] ?? $s['source'] ?? null))
                                ->filter(fn ($u) => is_string($u) && str_starts_with($u, 'http'))
                                ->unique()->values();
                            $constraints = $verdict['version_constraints'] ?? [];
                        @endphp
                        <div id="prova-context7" class="neo-border shadow-neo p-4 mt-8 bg-neo-white">
                            <div class="flex items-center justify-between flex-wrap gap-2 mb-3 pb-2 border-b-2 border-black">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-xs font-bold font-mono uppercase tracking-wide">Comprovação em documentação oficial</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($docStatus)
                                        <span class="{{ $docBadge }} border-2 border-black px-2 py-0.5 text-xs font-black uppercase">
                                            {{ $memory->doc_validation_status->label() }}
                                        </span>
                                    @endif
                                    @if(isset($verdict['confidence']))
                                        <span class="font-mono text-xs font-bold">{{ round($verdict['confidence'] * 100) }}% confiança</span>
                                    @endif
                                </div>
                            </div>

                            @if($library)
                                <div class="text-xs font-mono text-gray-600 mb-3">
                                    Biblioteca checada no Context7:
                                    <a href="{{ $libraryUrl }}" target="_blank" rel="noopener noreferrer" class="font-bold text-neo-magenta underline underline-offset-2 hover:text-black break-all">{{ $library }}</a>
                                </div>
                            @endif

                            @if(!empty($verdict['claims']))
                                <span class="text-[10px] font-mono uppercase text-gray-500 block mb-1">Afirmações verificadas</span>
                                <ul class="space-y-1.5 mb-3">
                                    @foreach($verdict['claims'] as $claim)
                                        @php
                                            $cv = $claim['verdict'] ?? '';
                                            $mark = ['supported' => '&#10003;', 'contradicted' => '&#10007;', 'unsupported' => '&#8212;'][$cv] ?? '?';
                                            $markColor = ['supported' => 'text-neo-green', 'contradicted' => 'text-neo-magenta', 'unsupported' => 'text-gray-400'][$cv] ?? 'text-gray-400';
                                        @endphp
                                        <li class="flex gap-2 items-start text-xs">
                                            <span class="{{ $markColor }} font-black leading-snug" aria-hidden="true">{!! $mark !!}</span>
                                            <span class="flex-1 font-mono">{{ $claim['claim'] ?? '' }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            @if(!empty($constraints))
                                <div class="mb-3">
                                    <span class="text-[10px] font-mono uppercase text-gray-500 block mb-1">Restrições de versão</span>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($constraints as $vc)
                                            <span class="bg-black text-white px-2 py-0.5 text-[10px] font-mono">{{ $vc }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if($sources->isNotEmpty())
                                {{-- O coração da prova: links diretos p/ a doc oficial de onde o veredito saiu. --}}
                                <div class="neo-border-sm bg-[#F0FDFA] p-3 mt-3">
                                    <span class="text-[10px] font-mono uppercase text-black font-black block mb-2">Documentação oficial — clique para comprovar</span>
                                    <ul class="space-y-1">
                                        @foreach($sources as $srcUrl)
                                            <li class="text-xs font-mono break-all flex gap-1.5 items-start">
                                                <span class="text-neo-magenta font-black shrink-0" aria-hidden="true">&rarr;</span>
                                                <a href="{{ $srcUrl }}" target="_blank" rel="noopener noreferrer" class="text-neo-magenta underline underline-offset-2 hover:text-black">{{ $srcUrl }}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if(!empty($report['note']))
                                <div class="text-xs font-mono text-gray-600 mt-3 bg-gray-50 neo-border-sm p-2">
                                    <span class="font-bold uppercase text-[10px] text-gray-500">Observação:</span> {{ $report['note'] }}
                                </div>
                            @endif

                            @if($memory->doc_validated_at)
                                <div class="text-[10px] font-mono text-gray-400 mt-2">Verificado em {{ $memory->doc_validated_at->format('d/m/Y \à\s H:i') }}</div>
                            @endif
                        </div>
                    @endif

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
