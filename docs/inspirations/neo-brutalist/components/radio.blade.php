@props([
    'nome'    => 'radio',
    'valor'   => null,
    'rotulo'  => null,
    'marcado' => false,
    'cor'     => 'teal',    // teal | yellow | magenta | green | purple
    'erro'    => null,
    'descricao' => null,
])

@php
$dot = match($cor) {
    'yellow'  => 'bg-neo-yellow',
    'magenta' => 'bg-neo-magenta',
    'green'   => 'bg-neo-green',
    'purple'  => 'bg-neo-purple',
    default   => 'bg-neo-teal',
};
@endphp

<div
    x-data="{ marcado: {{ $marcado ? 'true' : 'false' }} }"
    class="flex flex-col gap-1"
>
    <label class="inline-flex items-start gap-3 cursor-pointer group">
        {{-- Custom radio ring --}}
        <span
            class="relative flex-shrink-0 w-6 h-6 rounded-full neo-border transition-all duration-100 mt-0.5 bg-white"
            :class="marcado ? 'shadow-[1px_1px_0_#000] translate-x-px translate-y-px' : 'shadow-neo'"
        >
            <input
                type="radio"
                name="{{ $nome }}"
                value="{{ $valor }}"
                class="absolute inset-0 opacity-0 w-full h-full cursor-pointer"
                :checked="marcado"
                @change="marcado = true"
                @if($erro) aria-invalid="true" @endif
            />

            {{-- Inner dot --}}
            <span
                x-show="marcado"
                x-cloak
                x-transition:enter="transition ease-[cubic-bezier(0.34,1.56,0.64,1)] duration-200"
                x-transition:enter-start="opacity-0 scale-0"
                x-transition:enter-end="opacity-100 scale-100"
                class="absolute inset-[3px] rounded-full {{ $dot }} border-2 border-black"
                aria-hidden="true"
            ></span>
        </span>

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
        <p class="text-red-500 text-xs font-body font-bold ml-9" role="alert">{{ $erro }}</p>
    @endif
</div>
