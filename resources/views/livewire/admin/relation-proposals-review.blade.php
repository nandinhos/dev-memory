<div class="animate-fade-in-up">
    <p class="text-sm text-gray-600 font-mono mb-6">Revisão de relações propostas pela IA entre memórias. Aprovadas entram no knowledge graph; rejeitadas são arquivadas.</p>

    @forelse($proposals as $edge)
        <div class="bg-neo-white neo-border shadow-neo p-6 mb-6" wire:key="edge-{{ $edge->id }}">
            <div class="flex justify-between items-start gap-4 mb-3">
                <div class="min-w-0 flex-1">
                    <h2 class="font-heading text-xl m-0">
                        <span class="font-mono text-sm uppercase bg-black text-neo-white px-2 py-0.5 mr-2">{{ $edge->relation_type->value }}</span>
                        {{ $edge->source->memory?->title ?? $edge->source->label }}
                        <span class="text-gray-500 mx-2">→</span>
                        {{ $edge->target->memory?->title ?? $edge->target->label }}
                    </h2>
                    <p class="text-sm text-gray-700 mt-1 mb-0">
                        Origem: <code class="text-xs">{{ optional($edge->source->memory)->id ?? '—' }}</code>
                        · Destino: <code class="text-xs">{{ optional($edge->target->memory)->id ?? '—' }}</code>
                    </p>
                </div>
                <div class="flex flex-col items-end gap-2 flex-shrink-0">
                    <x-neo.badge variante="yellow">{{ $edge->status->value }}</x-neo.badge>
                    <span class="text-xs font-mono text-gray-500">confiança {{ number_format($edge->confidence, 2) }}</span>
                </div>
            </div>

            @if (! empty($edge->properties['rationale']))
                <p class="text-xs font-mono text-gray-500 italic mb-3">{{ $edge->properties['rationale'] }}</p>
            @endif

            @if ($edge->evidence->isNotEmpty())
                <div class="border-t-2 border-black/10 pt-3 space-y-1">
                    @foreach ($edge->evidence as $evidence)
                        <div class="text-sm flex items-start gap-2">
                            <span class="w-1.5 h-1.5 bg-black inline-block flex-shrink-0 mt-2"></span>
                            <div class="min-w-0">
                                <p class="m-0">{{ $evidence->excerpt ?? 'Evidência sem trecho' }}</p>
                                <p class="text-xs font-mono text-gray-500 mt-0.5 mb-0">origem: {{ $evidence->source_type }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($edge->status->value === 'proposed')
                <div class="flex gap-2 mt-4">
                    <x-neo.button variante="sucesso" tamanho="sm" wire:click="approve('{{ $edge->id }}')">APROVAR</x-neo.button>
                    <x-neo.button variante="destrutivo" tamanho="sm" wire:click="reject('{{ $edge->id }}')">REJEITAR</x-neo.button>
                </div>
            @endif
        </div>
    @empty
        <x-neo.empty-state titulo="Nenhuma proposta pendente" mensagem="Chame a tool MCP relation_extract_propose para gerar novas propostas de relação." />
    @endforelse

    @if ($proposals->hasPages())
        <div class="mt-6">{{ $proposals->links() }}</div>
    @endif
</div>
