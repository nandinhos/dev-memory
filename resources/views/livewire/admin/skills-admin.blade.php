<div class="animate-fade-in-up">
    <p class="text-sm text-gray-600 font-mono mb-6">Ciclo draft → aprovada → publicada (git).</p>

    @forelse($skills as $skill)
        @php
            $statusVariant = match ($skill->status->value) {
                'draft' => 'yellow',
                'approved' => 'teal',
                'published' => 'green',
                default => 'contorno',
            };
            $sources = $skill->manifest['evidence']['official_sources'] ?? [];
            $memoryIds = $skill->manifest['evidence']['lesson_ids'] ?? [];
        @endphp
        <div class="bg-neo-white neo-border shadow-neo p-6 mb-6" wire:key="skill-{{ $skill->id }}">
            <div class="flex justify-between items-start gap-4">
                <div class="min-w-0">
                    <h2 class="font-heading text-xl m-0">{{ $skill->name }}</h2>
                    <p class="text-xs font-mono text-gray-500 mt-1 mb-0">{{ $skill->slug }} · v{{ $skill->version }}</p>
                </div>
                <x-neo.badge variante="{{ $statusVariant }}">{{ $skill->status->label() }}</x-neo.badge>
            </div>

            <p class="text-sm text-gray-700 mt-2">{{ $skill->manifest['purpose'] ?? '' }}</p>

            <div class="flex flex-wrap gap-3 text-xs font-mono text-gray-500 mt-2">
                <span>{{ count($memoryIds) }} memória(s)</span>
                <span>{{ count($sources) }} fonte(s) oficial(is)</span>
            </div>

            <div class="flex gap-2 mt-4 items-center flex-wrap">
                @if ($skill->status->value === 'draft')
                    <x-neo.button variante="sucesso" tamanho="sm" wire:click="approve('{{ $skill->id }}')">APROVAR</x-neo.button>
                @elseif ($skill->status->value === 'approved')
                    <x-neo.button variante="primario" tamanho="sm" wire:click="publish('{{ $skill->id }}')">PUBLICAR</x-neo.button>
                @elseif ($skill->status->value === 'published')
                    <x-neo.button variante="contorno" tamanho="sm" wire:click="publish('{{ $skill->id }}')">REPUBLICAR (v+1)</x-neo.button>
                @endif
                <x-neo.button variante="texto" tamanho="sm" wire:click="toggle('{{ $skill->id }}')">
                    {{ $expandedId === $skill->id ? 'ocultar manifesto' : 'ver manifesto' }}
                </x-neo.button>
            </div>

            @if ($expandedId === $skill->id)
                <div class="mt-4 border-t-2 border-black/10 pt-3">
                    <pre class="text-xs font-mono bg-neo-bg neo-border-sm p-3 overflow-x-auto">{{ json_encode($skill->manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            @endif
        </div>
    @empty
        <x-neo.empty-state titulo="Nenhuma skill" mensagem="Compile grupos aprovados via php artisan memory:compile-skills." />
    @endforelse
</div>
