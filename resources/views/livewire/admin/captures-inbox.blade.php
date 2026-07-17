<div class="animate-fade-in-up">
    <p class="text-sm text-gray-600 font-mono mb-6">Entradas do pipeline de curadoria.</p>

    @forelse($captures as $capture)
        @php
            $statusVariant = match ($capture->status->value) {
                'pending' => 'contorno',
                'sanitized' => 'yellow',
                'curated' => 'green',
                'discarded' => 'salmon',
                'failed' => 'magenta',
                default => 'contorno',
            };
        @endphp
        <div class="bg-neo-white neo-border shadow-neo p-5 mb-4" wire:key="cap-{{ $capture->id }}">
            <div class="flex justify-between items-start gap-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 text-xs font-mono text-gray-500 flex-wrap">
                        <span>{{ $capture->source_system }}</span>
                        @if ($capture->trigger_event)<span>· {{ $capture->trigger_event }}</span>@endif
                        @if ($capture->source_project)<span>· {{ $capture->source_project }}</span>@endif
                    </div>
                    <p class="text-sm mt-1 mb-0">{{ \Illuminate\Support\Str::limit($capture->sanitized_content ?? $capture->raw_content, 140) }}</p>
                    @if ($capture->memory)
                        <a href="{{ route('memories.show', $capture->memory) }}" wire:navigate class="text-xs font-mono underline">→ {{ $capture->memory->title }}</a>
                    @endif
                </div>
                <x-neo.badge variante="{{ $statusVariant }}">{{ $capture->status->label() }}</x-neo.badge>
            </div>

            @php $hasRedactions = ! empty($capture->metadata['redactions']); @endphp
            <x-neo.button variante="texto" tamanho="sm" wire:click="toggle('{{ $capture->id }}')">
                {{ $expandedId === $capture->id ? 'ocultar' : ($hasRedactions ? 'bruto vs sanitizado' : 'ver conteúdo') }}
            </x-neo.button>

            @if ($expandedId === $capture->id)
                <div class="mt-3 border-t-2 border-black/10 pt-3">
                    @if ($hasRedactions)
                        {{-- Houve redação de segredos: comparação lado a lado faz sentido --}}
                        <div class="grid md:grid-cols-2 gap-3">
                            <div>
                                <span class="text-xs font-mono font-bold uppercase text-gray-500">Bruto</span>
                                <pre class="text-xs font-mono bg-neo-bg neo-border-sm p-2 overflow-x-auto mt-1 whitespace-pre-wrap">{{ $capture->raw_content }}</pre>
                            </div>
                            <div>
                                <span class="text-xs font-mono font-bold uppercase text-neo-magenta">Sanitizado</span>
                                <pre class="text-xs font-mono bg-neo-bg neo-border-sm p-2 overflow-x-auto mt-1 whitespace-pre-wrap">{{ $capture->sanitized_content }}</pre>
                                <div class="text-xs font-mono text-gray-500 mt-1">Redações: {{ json_encode($capture->metadata['redactions']) }}</div>
                            </div>
                        </div>
                    @else
                        {{-- Sem segredos: bruto == sanitizado, mostra um único bloco --}}
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-mono font-bold uppercase">Conteúdo</span>
                            <span class="text-[10px] font-mono uppercase bg-neo-green border border-black px-1.5 py-0.5">sem segredos · nada redigido</span>
                        </div>
                        <pre class="text-xs font-mono bg-neo-bg neo-border-sm p-2 overflow-x-auto mt-1 whitespace-pre-wrap">{{ $capture->sanitized_content ?? $capture->raw_content }}</pre>
                    @endif
                </div>
            @endif
        </div>
    @empty
        <x-neo.empty-state titulo="Nenhuma capture" mensagem="Capturas chegam via memory:process-captures ou ingestão externa." />
    @endforelse

    <div class="mt-4">{{ $captures->links() }}</div>
</div>
