@props([
    'texto'    => '',
    'posicao'  => 'top',   // top | bottom | left | right
    'cor'      => 'black', // black | teal | yellow | magenta
])

@php
$tooltipBg = match($cor) {
    'teal'    => 'bg-neo-teal border-black text-black',
    'yellow'  => 'bg-neo-yellow border-black text-black',
    'magenta' => 'bg-neo-magenta border-black text-black',
    default   => 'bg-black border-black text-white',
};

[$posClasses, $arrowClasses] = match($posicao) {
    'bottom' => [
        'top-full mt-2 left-1/2 -translate-x-1/2',
        'bottom-full left-1/2 -translate-x-1/2 border-l-[6px] border-r-[6px] border-b-[6px] border-l-transparent border-r-transparent border-b-current mb-0',
    ],
    'left' => [
        'right-full mr-2 top-1/2 -translate-y-1/2',
        'left-full top-1/2 -translate-y-1/2 border-t-[6px] border-b-[6px] border-l-[6px] border-t-transparent border-b-transparent border-l-current ml-0',
    ],
    'right' => [
        'left-full ml-2 top-1/2 -translate-y-1/2',
        'right-full top-1/2 -translate-y-1/2 border-t-[6px] border-b-[6px] border-r-[6px] border-t-transparent border-b-transparent border-r-current mr-0',
    ],
    default => [
        'bottom-full mb-2 left-1/2 -translate-x-1/2',
        'top-full left-1/2 -translate-x-1/2 border-l-[6px] border-r-[6px] border-t-[6px] border-l-transparent border-r-transparent border-t-current mt-0',
    ],
};
@endphp

<span
    x-data="{ visivel: false }"
    class="relative inline-flex"
    @mouseenter="visivel = true"
    @mouseleave="visivel = false"
    @focusin="visivel = true"
    @focusout="visivel = false"
>
    {{-- Trigger --}}
    {{ $slot }}

    {{-- Tooltip box --}}
    <span
        x-show="visivel"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        role="tooltip"
        class="absolute z-50 pointer-events-none {{ $posClasses }}"
    >
        {{-- Arrow --}}
        <span class="absolute w-0 h-0 {{ $arrowClasses }}" aria-hidden="true"></span>

        {{-- Box --}}
        <span class="relative block whitespace-nowrap {{ $tooltipBg }} border-2 px-2.5 py-1 text-xs font-body font-bold shadow-[2px_2px_0_rgba(0,0,0,0.3)]">
            {{ $texto }}
        </span>
    </span>
</span>
