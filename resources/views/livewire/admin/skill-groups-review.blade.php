<div class="animate-fade-in-up">
    <p class="text-sm text-gray-600 font-mono mb-6">Revisão de agrupamentos propostos pela IA.</p>

    @forelse($groups as $group)
        @php
            $statusVariant = match ($group->status->value) {
                'proposed' => 'yellow',
                'approved' => 'green',
                'rejected' => 'salmon',
                'compiled' => 'purple',
                default => 'contorno',
            };
        @endphp
        <div class="bg-neo-white neo-border shadow-neo p-6 mb-6" wire:key="group-{{ $group->id }}">
            <div class="flex justify-between items-start gap-4 mb-3">
                <div>
                    <h2 class="font-heading text-xl m-0">{{ $group->name }}</h2>
                    <p class="text-sm text-gray-700 mt-1 mb-0">{{ $group->purpose }}</p>
                </div>
                <div class="flex flex-col items-end gap-2 flex-shrink-0">
                    <x-neo.badge variante="{{ $statusVariant }}">{{ $group->status->label() }}</x-neo.badge>
                    <span class="text-xs font-mono text-gray-500">coesão {{ number_format($group->cohesion, 2) }}</span>
                </div>
            </div>

            <p class="text-xs font-mono text-gray-500 italic mb-3">{{ $group->rationale }}</p>

            <div class="border-t-2 border-black/10 pt-3 space-y-1">
                @foreach ($group->memories as $memory)
                    <div class="text-sm flex items-center gap-2">
                        <span class="w-1.5 h-1.5 bg-black inline-block flex-shrink-0"></span>
                        {{ $memory->title }}
                    </div>
                @endforeach
            </div>

            @if (in_array($group->status->value, ['proposed', 'rejected']))
                <div class="flex gap-2 mt-4">
                    <x-neo.button variante="sucesso" tamanho="sm" wire:click="approve('{{ $group->id }}')">APROVAR</x-neo.button>
                    @if ($group->status->value === 'proposed')
                        <x-neo.button variante="destrutivo" tamanho="sm" wire:click="reject('{{ $group->id }}')">REJEITAR</x-neo.button>
                    @endif
                </div>
            @elseif ($group->status->value === 'approved')
                <div class="flex gap-2 mt-4 items-center flex-wrap">
                    <x-neo.button variante="destrutivo" tamanho="sm" wire:click="reject('{{ $group->id }}')">REJEITAR</x-neo.button>
                    <span class="text-xs font-mono text-gray-500">Compilar via <code>php artisan memory:compile-skills</code></span>
                </div>
            @endif
        </div>
    @empty
        <x-neo.empty-state titulo="Nenhum grupo" mensagem="Rode php artisan memory:group-skills para gerar propostas de agrupamento." />
    @endforelse
</div>
