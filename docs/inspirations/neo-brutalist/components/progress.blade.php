@props([
    'valor'       => 0,         // 0–100
    'variante'    => 'padrao',  // padrao | sucesso | aviso | erro | roxo
    'rotulo'      => null,
    'mostrarValor' => true,
    'animado'     => true,
    'listrado'    => false,
])

@php
[$fillBg, $labelBg] = match($variante) {
    'sucesso' => ['bg-neo-green',   'bg-neo-green'],
    'aviso'   => ['bg-neo-yellow',  'bg-neo-yellow'],
    'erro'    => ['bg-neo-magenta', 'bg-neo-magenta'],
    'roxo'    => ['bg-neo-purple',  'bg-neo-purple'],
    default   => ['bg-neo-teal',    'bg-neo-teal'],
};

$clampedValor = max(0, min(100, (int)$valor));
@endphp

<div class="w-full space-y-1">
    @if($rotulo || $mostrarValor)
        <div class="flex justify-between items-baseline">
            @if($rotulo)
                <span class="text-xs font-bold font-body uppercase tracking-wider">{{ $rotulo }}</span>
            @endif
            @if($mostrarValor)
                <span class="text-xs font-heading font-bold tabular-nums ml-auto">{{ $clampedValor }}%</span>
            @endif
        </div>
    @endif

    {{-- Track --}}
    <div
        class="relative w-full h-7 bg-white neo-border shadow-neo overflow-hidden"
        role="progressbar"
        aria-valuenow="{{ $clampedValor }}"
        aria-valuemin="0"
        aria-valuemax="100"
        @if($rotulo) aria-label="{{ $rotulo }}" @endif
    >
        {{-- Background stripes --}}
        <div
            class="absolute inset-0 opacity-5"
            style="background: repeating-linear-gradient(90deg, #000 0px, #000 1px, transparent 1px, transparent 12px);"
            aria-hidden="true"
        ></div>

        {{-- Fill --}}
        <div
            class="absolute inset-y-0 left-0 {{ $fillBg }} transition-all {{ $animado ? 'duration-1000 ease-out' : '' }} flex items-center justify-end"
            style="width: {{ $clampedValor }}%;"
        >
            @if($listrado)
                <div
                    class="absolute inset-0 opacity-20"
                    style="background: repeating-linear-gradient(-45deg, transparent, transparent 6px, rgba(0,0,0,0.3) 6px, rgba(0,0,0,0.3) 12px);"
                    aria-hidden="true"
                ></div>
            @endif

            {{-- Right edge notch --}}
            @if($clampedValor > 5 && $clampedValor < 100)
                <div class="flex-shrink-0 w-1 h-full bg-black opacity-30" aria-hidden="true"></div>
            @endif
        </div>

        {{-- Milestone markers --}}
        @foreach([25, 50, 75] as $mark)
            <div
                class="absolute top-0 bottom-0 w-px bg-black opacity-10"
                style="left: {{ $mark }}%"
                aria-hidden="true"
            ></div>
        @endforeach
    </div>
</div>
