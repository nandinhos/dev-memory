<div class="animate-fade-in-up">
    <x-neo.page-header titulo="DASHBOARD" subtitulo="Visão geral do sistema">
        <x-slot:actions>
            <a href="{{ route('memories.create') }}" class="btn-neo bg-neo-teal neo-border-sm shadow-neo px-4 py-2 font-heading text-sm hover:bg-neo-yellow transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Nova Memória
            </a>
        </x-slot:actions>
    </x-neo.page-header>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="card-neo bg-neo-white neo-border shadow-neo p-4 text-center">
            <div class="text-4xl font-heading text-neo-magenta mb-1">{{ $totalMemories }}</div>
            <div class="text-xs font-mono uppercase tracking-wider text-gray-500">Total</div>
        </div>
        
        <div class="card-neo bg-neo-white neo-border shadow-neo p-4 text-center">
            <div class="text-4xl font-heading text-neo-yellow mb-1">{{ $totalErrors }}</div>
            <div class="text-xs font-mono uppercase tracking-wider text-gray-500">Erros</div>
        </div>
        
        <div class="card-neo bg-neo-white neo-border shadow-neo p-4 text-center">
            <div class="text-4xl font-heading text-neo-teal mb-1">{{ $totalLessons }}</div>
            <div class="text-xs font-mono uppercase tracking-wider text-gray-500">Lições</div>
        </div>
        
        <div class="card-neo bg-neo-white neo-border shadow-neo p-4 text-center">
            <div class="text-4xl font-heading text-neo-green mb-1">{{ $totalBestPractices }}</div>
            <div class="text-xs font-mono uppercase tracking-wider text-gray-500">Boas Práticas</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="card-neo bg-neo-white neo-border shadow-neo p-6">
            <h3 class="font-heading text-lg mb-4 pb-2 border-b-2 border-black">Validação</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-mono">Validados</span>
                    <span class="inline-block bg-neo-green border-2 border-black px-3 py-1 text-xs font-bold font-heading">{{ $totalValidated }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-mono">Pendentes</span>
                    <span class="inline-block bg-neo-salmon border-2 border-black px-3 py-1 text-xs font-bold font-heading">{{ $totalPending }}</span>
                </div>
                @if($totalMemories > 0)
                    <div class="pt-2">
                        <div class="flex justify-between text-xs font-mono mb-1">
                            <span>Taxa de validação</span>
                            <span>{{ round(($totalValidated / $totalMemories) * 100) }}%</span>
                        </div>
                        <div class="h-4 bg-gray-200 overflow-hidden border-2 border-black">
                            <div class="h-full bg-neo-green transition-all duration-300" style="width: {{ round(($totalValidated / $totalMemories) * 100) }}%"></div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="card-neo bg-neo-white neo-border shadow-neo p-6">
            <h3 class="font-heading text-lg mb-4 pb-2 border-b-2 border-black">Escopo</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-mono">Global</span>
                    <span class="inline-block bg-neo-purple border-2 border-black px-3 py-1 text-xs font-bold font-heading">{{ $totalGlobal }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-mono">Projeto</span>
                    <span class="inline-block bg-neo-teal border-2 border-black px-3 py-1 text-xs font-bold font-heading">{{ $totalProject }}</span>
                </div>
            </div>
        </div>

        <div class="card-neo bg-neo-white neo-border shadow-neo p-6">
            <h3 class="font-heading text-lg mb-4 pb-2 border-b-2 border-black">Top Stacks</h3>
            <div class="space-y-3">
                @forelse($topStacks as $index => $stack)
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-mono flex items-center gap-2">
                            <span class="w-5 h-5 bg-black text-white flex items-center justify-center text-[10px] font-bold">{{ $index + 1 }}</span>
                            {{ $stack['stack'] }}
                        </span>
                        <span class="inline-block bg-neo-yellow border-2 border-black px-2 py-0.5 text-xs font-bold font-heading">{{ $stack['count'] }}</span>
                    </div>
                @empty
                    <p class="text-sm font-mono text-gray-500">Nenhuma stack registrada</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card-neo bg-neo-white neo-border shadow-neo p-6">
        <h3 class="font-heading text-lg mb-4 pb-2 border-b-2 border-black">Memórias Recentes</h3>
        <div class="space-y-3">
            @forelse($recentMemories as $memory)
                <a href="{{ route('memories.show', $memory['id']) }}" class="flex items-center justify-between p-3 neo-border-sm shadow-neo-sm hover:shadow-neo transition-shadow group">
                    <div class="flex items-center gap-3">
                        @php
                            $typeColors = [
                                'error' => 'bg-neo-magenta',
                                'lesson' => 'bg-neo-yellow',
                                'best_practice' => 'bg-neo-green',
                            ];
                            $typeColor = $typeColors[$memory['type_color']] ?? 'bg-neo-teal';
                        @endphp
                        <span class="inline-block w-2 h-2 {{ $typeColor }} border border-black rounded-full"></span>
                        <span class="font-mono text-sm group-hover:text-neo-magenta transition-colors">{{ Str::limit($memory['title'], 50) }}</span>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-xs font-mono text-gray-500">{{ $memory['created_at'] }}</span>
                        <span class="text-xs font-mono text-gray-400 group-hover:text-neo-magenta">→</span>
                    </div>
                </a>
            @empty
                <p class="text-sm font-mono text-gray-500 text-center py-4">Nenhuma memória cadastrada</p>
            @endforelse
        </div>
        
        <div class="mt-4 pt-4 border-t-2 border-black text-center">
            <a href="{{ route('memories.index') }}" class="btn-neo bg-neo-white neo-border-sm shadow-neo-sm px-6 py-2 font-heading text-sm hover:bg-neo-yellow transition-colors">
                Ver Todas as Memórias →
            </a>
        </div>
    </div>
</div>
