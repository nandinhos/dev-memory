@props([
    'rotulo'      => '',
    'valor'       => '',
    'tendencia'   => null,
    'porcentagem' => null,
    'icone'       => null,
    'destaque'    => false,
])

@php
$bgCard = $destaque ? 'bg-neo-yellow' : 'bg-white';
@endphp

<div {{ $attributes->merge(['class' => "{$bgCard} neo-border shadow-neo p-5"]) }}>
    <div class="flex items-start justify-between gap-3">
        {{-- Conteúdo principal --}}
        <div class="flex-1 min-w-0">
            {{-- Rótulo --}}
            <p class="font-body text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">{{ $rotulo }}</p>

            {{-- Valor --}}
            <p class="font-heading text-4xl font-bold uppercase leading-none mb-3">{{ $valor }}</p>

            {{-- Tendência --}}
            @if($tendencia === 'up')
                <span class="inline-flex items-center gap-1 bg-neo-green border-2 border-black px-2 py-0.5 text-xs font-bold font-body">
                    <span aria-hidden="true">↑</span>
                    @if($porcentagem){{ $porcentagem }}@endif
                    <span class="sr-only">Alta de {{ $porcentagem }}</span>
                </span>
            @elseif($tendencia === 'down')
                <span class="inline-flex items-center gap-1 bg-neo-magenta border-2 border-black px-2 py-0.5 text-xs font-bold font-body">
                    <span aria-hidden="true">↓</span>
                    @if($porcentagem){{ $porcentagem }}@endif
                    <span class="sr-only">Queda de {{ $porcentagem }}</span>
                </span>
            @endif
        </div>

        {{-- Ícone --}}
        @if($icone)
            <div class="bg-neo-teal border-2 border-black w-10 h-10 flex items-center justify-center shadow-[2px_2px_0_#000] flex-shrink-0" aria-hidden="true">
                {!! $icone !!}
            </div>
        @endif
    </div>

    {{-- Slot adicional --}}
    @if($slot->isNotEmpty())
        <div class="mt-3 pt-3 border-t-2 border-black font-body text-xs text-gray-600">
            {{ $slot }}
        </div>
    @endif
</div>
