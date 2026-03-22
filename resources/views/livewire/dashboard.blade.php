<div class="animate-fade-in-up">
    <x-neo.page-header titulo="DASHBOARD" subtitulo="Visão geral do sistema">
        <x-slot:actions>
            <a href="{{ route('memories.create') }}" class="btn-neo bg-neo-green neo-border-sm shadow-neo px-4 py-2 font-heading text-sm hover:bg-neo-yellow transition-colors flex items-center gap-2 font-bold">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Nova Memória
            </a>
        </x-slot:actions>
    </x-neo.page-header>

    {{-- Stat Cards Coloridos --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="card-neo bg-neo-teal neo-border shadow-neo p-5 text-center hover:shadow-neo-lg transition-all">
            <div class="text-5xl font-heading text-black mb-1 leading-none">{{ $totalMemories }}</div>
            <div class="text-[10px] font-mono uppercase tracking-widest text-black/70 mt-2">Total</div>
        </div>
        <div class="card-neo bg-neo-magenta neo-border shadow-neo p-5 text-center hover:shadow-neo-lg transition-all">
            <div class="text-5xl font-heading text-black mb-1 leading-none">{{ $totalErrors }}</div>
            <div class="text-[10px] font-mono uppercase tracking-widest text-black/70 mt-2">Erros</div>
        </div>
        <div class="card-neo bg-neo-yellow neo-border shadow-neo p-5 text-center hover:shadow-neo-lg transition-all">
            <div class="text-5xl font-heading text-black mb-1 leading-none">{{ $totalLessons }}</div>
            <div class="text-[10px] font-mono uppercase tracking-widest text-black/70 mt-2">Lições</div>
        </div>
        <div class="card-neo bg-neo-green neo-border shadow-neo p-5 text-center hover:shadow-neo-lg transition-all">
            <div class="text-5xl font-heading text-black mb-1 leading-none">{{ $totalBestPractices }}</div>
            <div class="text-[10px] font-mono uppercase tracking-widest text-black/70 mt-2">Boas Práticas</div>
        </div>
    </div>

    {{-- Seções de Stats --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

        {{-- Validação — fundo salmon --}}
        <div class="card-neo bg-neo-salmon neo-border shadow-neo p-6">
            <h3 class="font-heading text-lg mb-4 pb-2 border-b-2 border-black flex items-center gap-2">
                <span class="bg-black text-white px-1.5 py-0.5 text-[10px] font-mono font-bold">01</span>
                Validação
            </h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-mono font-bold">Validados</span>
                    <span class="inline-block bg-neo-green border-2 border-black px-3 py-1 text-sm font-bold font-heading shadow-neo-sm">{{ $totalValidated }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-mono font-bold">Pendentes</span>
                    <span class="inline-block bg-neo-white border-2 border-black px-3 py-1 text-sm font-bold font-heading shadow-neo-sm">{{ $totalPending }}</span>
                </div>
                @if($totalMemories > 0)
                    <div class="pt-2">
                        <div class="flex justify-between text-xs font-mono mb-1 font-bold">
                            <span>Taxa de validação</span>
                            <span>{{ round(($totalValidated / $totalMemories) * 100) }}%</span>
                        </div>
                        <div class="h-5 bg-white/60 overflow-hidden border-2 border-black">
                            <div class="h-full bg-neo-green transition-all duration-300 border-r-2 border-black" style="width: {{ round(($totalValidated / $totalMemories) * 100) }}%"></div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Escopo — fundo purple --}}
        <div class="card-neo bg-neo-purple neo-border shadow-neo p-6">
            <h3 class="font-heading text-lg mb-4 pb-2 border-b-2 border-white/40 flex items-center gap-2 text-white">
                <span class="bg-white text-black px-1.5 py-0.5 text-[10px] font-mono font-bold">02</span>
                Escopo
            </h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-mono font-bold text-white">Global</span>
                    <span class="inline-block bg-neo-teal border-2 border-black px-3 py-1 text-sm font-bold font-heading shadow-neo-sm">{{ $totalGlobal }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-mono font-bold text-white">Projeto</span>
                    <span class="inline-block bg-neo-white border-2 border-black px-3 py-1 text-sm font-bold font-heading shadow-neo-sm">{{ $totalProject }}</span>
                </div>
            </div>
            @if($totalMemories > 0)
                <div class="mt-6 pt-4 border-t-2 border-white/20">
                    <p class="text-[10px] font-mono text-white/60 uppercase tracking-widest mb-2">Distribuição de escopo</p>
                    <div class="flex h-4 border-2 border-white/40 overflow-hidden">
                        <div class="h-full bg-neo-teal border-r border-black/20 transition-all" style="width: {{ round(($totalGlobal / $totalMemories) * 100) }}%"></div>
                        <div class="h-full bg-white/20 flex-1"></div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Top Stacks — fundo yellow --}}
        <div class="card-neo bg-neo-yellow neo-border shadow-neo p-6">
            <h3 class="font-heading text-lg mb-4 pb-2 border-b-2 border-black flex items-center gap-2">
                <span class="bg-black text-white px-1.5 py-0.5 text-[10px] font-mono font-bold">03</span>
                Top Stacks
            </h3>
            <div class="space-y-3">
                @forelse($topStacks as $index => $stack)
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-mono font-bold flex items-center gap-2">
                            <span class="w-6 h-6 bg-black text-white flex items-center justify-center text-[10px] font-bold font-mono shadow-neo-sm">{{ $index + 1 }}</span>
                            {{ $stack['stack'] }}
                        </span>
                        <span class="inline-block bg-black text-white border-2 border-black px-2 py-0.5 text-xs font-bold font-heading shadow-neo-sm">{{ $stack['count'] }}</span>
                    </div>
                @empty
                    <p class="text-sm font-mono text-black/60 italic">Nenhuma stack registrada</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Memórias Recentes --}}
    <div class="card-neo bg-neo-white neo-border shadow-neo overflow-hidden">
        <div class="bg-black px-6 py-3 flex items-center gap-3">
            <span class="bg-neo-yellow text-black px-1.5 py-0.5 text-[10px] font-mono font-bold">04</span>
            <h3 class="font-heading text-lg text-white m-0">Memórias Recentes</h3>
        </div>
        <div class="p-6">
            <div class="space-y-2">
                @forelse($recentMemories as $index => $memory)
                    @php
                        $typeColors = [
                            'error' => 'bg-neo-magenta',
                            'lesson' => 'bg-neo-yellow',
                            'best_practice' => 'bg-neo-green',
                        ];
                        $typeColor = $typeColors[$memory['type_color']] ?? 'bg-neo-teal';
                    @endphp
                    <a href="{{ route('memories.show', $memory['id']) }}"
                       class="flex items-center gap-0 neo-border-sm hover:shadow-neo transition-all group overflow-hidden"
                       style="background: {{ $index % 2 === 0 ? '#FFFFFF' : '#F9F9F5' }}">
                        <div class="w-1.5 self-stretch flex-shrink-0 {{ $typeColor }}"></div>
                        <div class="flex items-center justify-between flex-1 gap-3 min-w-0 px-3 py-2.5">
                            <span class="font-mono text-sm group-hover:text-neo-magenta transition-colors truncate">{{ Str::limit($memory['title'], 55) }}</span>
                            <div class="flex items-center gap-3 flex-shrink-0">
                                <span class="text-[10px] font-mono text-gray-400 hidden sm:block">{{ $memory['created_at'] }}</span>
                                <span class="text-xs font-mono text-gray-400 group-hover:text-neo-magenta transition-colors">→</span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="text-center py-8">
                        <p class="text-sm font-mono text-gray-400">Nenhuma memória cadastrada ainda.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-6 pt-4 border-t-2 border-black text-center">
                <a href="{{ route('memories.index') }}" class="btn-neo bg-neo-teal neo-border-sm shadow-neo px-6 py-2 font-heading text-sm hover:bg-neo-yellow transition-colors">
                    Ver Todas as Memórias →
                </a>
            </div>
        </div>
    </div>
</div>
