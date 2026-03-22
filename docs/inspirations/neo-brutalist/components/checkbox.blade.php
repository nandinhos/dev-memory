@props([
    'id'            => null,
    'rotulo'        => null,
    'marcado'       => false,
    'indeterminado' => false,
    'cor'           => 'teal',   // teal | yellow | magenta | green | purple
    'erro'          => null,
    'descricao'     => null,
])

@php
$bg = match($cor) {
    'yellow'  => 'bg-neo-yellow',
    'magenta' => 'bg-neo-magenta',
    'green'   => 'bg-neo-green',
    'purple'  => 'bg-neo-purple',
    default   => 'bg-neo-teal',
};
@endphp

<div
    x-data="{ marcado: {{ $marcado ? 'true' : 'false' }}, indeterminado: {{ $indeterminado ? 'true' : 'false' }} }"
    class="flex flex-col gap-1"
>
    <label
        for="{{ $id }}"
        class="inline-flex items-start gap-3 cursor-pointer group"
    >
        {{-- Custom checkbox box --}}
        <span
            class="relative flex-shrink-0 w-6 h-6 neo-border transition-all duration-100 mt-0.5"
            :class="(marcado || indeterminado) ? '{{ $bg }} shadow-[1px_1px_0_#000]' : 'bg-white shadow-neo'"
            style="(marcado || indeterminado) && 'transform: translate(1px,1px)'"
        >
            {{-- Hidden real input --}}
            <input
                type="checkbox"
                id="{{ $id }}"
                class="absolute inset-0 opacity-0 w-full h-full cursor-pointer"
                :checked="marcado"
                :indeterminate="indeterminado"
                @change="marcado = $event.target.checked; indeterminado = false"
                @if($erro) aria-describedby="{{ $id }}-erro" aria-invalid="true" @endif
            />

            {{-- Checkmark SVG --}}
            <svg
                x-show="marcado && !indeterminado"
                x-cloak
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-50"
                x-transition:enter-end="opacity-100 scale-100"
                class="absolute inset-0 w-full h-full p-0.5"
                viewBox="0 0 20 20" fill="none"
                stroke="black" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"
                aria-hidden="true"
            >
                <polyline points="4,10 8,15 16,5"/>
            </svg>

            {{-- Indeterminate dash --}}
            <span
                x-show="indeterminado"
                x-cloak
                class="absolute inset-0 flex items-center justify-center"
                aria-hidden="true"
            >
                <span class="w-3 h-0.5 bg-black"></span>
            </span>
        </span>

        {{-- Label text --}}
        @if($rotulo)
            <span class="font-body text-sm font-bold leading-tight group-hover:underline underline-offset-2">
                {{ $rotulo }}
                @if($descricao)
                    <span class="block font-normal text-xs text-gray-600 mt-0.5">{{ $descricao }}</span>
                @endif
            </span>
        @endif
    </label>

    @if($erro)
        <p id="{{ $id }}-erro" class="text-red-500 text-xs font-body font-bold ml-9" role="alert">{{ $erro }}</p>
    @endif
</div>
