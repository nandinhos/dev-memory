@props(['etapas' => [], 'atual' => 1])

<div class="flex items-center gap-0 overflow-x-auto" role="list">
    @foreach($etapas as $index => $etapa)
        <div class="flex items-center" role="listitem">
            <div class="neo-border shadow-neo-sm px-3 py-2 font-heading font-bold uppercase text-sm whitespace-nowrap {{ $index + 1 < $atual ? 'bg-neo-green' : ($index + 1 === $atual ? 'bg-neo-teal' : 'bg-white') }}">
                @if($index + 1 < $atual)
                    <i class="fas fa-check mr-1" aria-hidden="true"></i>
                @else
                    <span class="inline-flex items-center justify-center w-5 h-5 border-2 border-black text-xs font-bold mr-1">{{ $index + 1 }}</span>
                @endif
                {{ $etapa }}
            </div>
            @if(!$loop->last)
                <div class="w-6 h-1 bg-black flex-shrink-0"></div>
            @endif
        </div>
    @endforeach
</div>
