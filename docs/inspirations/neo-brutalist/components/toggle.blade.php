@props([
    'id'      => null,
    'rotulo'  => null,
    'ligado'  => false,
    'cor'     => 'teal',      // teal | yellow | magenta | green | purple
    'tamanho' => 'md',        // sm | md | lg
    'erro'    => null,
])

@php
$track = match($cor) {
    'yellow'  => 'bg-neo-yellow',
    'magenta' => 'bg-neo-magenta',
    'green'   => 'bg-neo-green',
    'purple'  => 'bg-neo-purple',
    default   => 'bg-neo-teal',
};

[$trackW, $trackH, $thumbSize, $translate] = match($tamanho) {
    'sm' => ['w-10', 'h-5',  'w-4 h-4',  'translate-x-[22px]'],
    'lg' => ['w-20', 'h-10', 'w-8 h-8',  'translate-x-[42px]'],
    default => ['w-14', 'h-7', 'w-5 h-5', 'translate-x-[28px]'],
};
@endphp

<div
    x-data="{ ligado: {{ $ligado ? 'true' : 'false' }} }"
    class="inline-flex flex-col gap-1"
>
    @if($rotulo)
        <label
            for="{{ $id }}"
            class="text-xs font-bold font-body uppercase tracking-wider cursor-pointer"
            @click="ligado = !ligado; $el.closest('[x-data]').querySelector('button').focus()"
        >{{ $rotulo }}</label>
    @endif

    <button
        id="{{ $id }}"
        type="button"
        role="switch"
        :aria-checked="ligado"
        @click="ligado = !ligado"
        class="relative inline-flex items-center {{ $trackW }} {{ $trackH }} neo-border transition-all duration-150 focus:outline-none focus-visible:ring-4 focus-visible:ring-black focus-visible:ring-offset-2"
        :class="ligado ? '{{ $track }} shadow-[2px_2px_0_#000]' : 'bg-white shadow-neo'"
        :style="ligado ? 'transform: translate(2px, 2px)' : ''"
    >
        {{-- Track stripes (off state decoration) --}}
        <span
            x-show="!ligado"
            class="absolute inset-0 opacity-10"
            style="background: repeating-linear-gradient(90deg, #000 0px, #000 2px, transparent 2px, transparent 8px);"
            aria-hidden="true"
        ></span>

        {{-- Thumb --}}
        <span
            class="absolute top-0.5 left-0.5 {{ $thumbSize }} bg-white neo-border transition-all duration-200 ease-[cubic-bezier(0.34,1.56,0.64,1)] flex items-center justify-center"
            :class="ligado ? '{{ $translate }}' : 'translate-x-0'"
            :style="ligado ? 'box-shadow: 1px 1px 0 #000' : 'box-shadow: 2px 2px 0 #000'"
        >
            <svg x-show="ligado" x-cloak class="w-2.5 h-2.5" viewBox="0 0 10 8" fill="none" stroke="black" stroke-width="2.5" stroke-linecap="round">
                <polyline points="1,4 4,7 9,1"/>
            </svg>
        </span>

        {{-- ON/OFF label --}}
        <span
            class="absolute right-1 font-heading text-[8px] font-bold select-none transition-opacity"
            :class="ligado ? 'opacity-0' : 'opacity-40'"
            aria-hidden="true"
        >OFF</span>
        <span
            class="absolute left-1 font-heading text-[8px] font-bold select-none transition-opacity"
            :class="ligado ? 'opacity-80' : 'opacity-0'"
            aria-hidden="true"
        >ON</span>
    </button>

    @if($erro)
        <p class="text-red-500 text-xs font-body font-bold">{{ $erro }}</p>
    @endif
</div>
