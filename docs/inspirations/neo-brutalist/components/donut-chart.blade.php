@props([
    'dados'        => [],
    'tamanho'       => 'md',
    'mostrarCentro' => true,
    'rotuloCentro'  => null,
    'animado'      => true,
])

@php
$coresPadrao = ['#22D3EE', '#FDE047', '#E879F9', '#4ADE80', '#A855F7', '#FB923C'];
$total = collect($dados)->sum('valor') ?? 1;

$tamanhos = match($tamanho) {
    'sm' => ['svg' => 80, 'stroke' => 12, 'raio' => 28, 'texto' => 14, 'subtexto' => 8],
    'lg' => ['svg' => 160, 'stroke' => 24, 'raio' => 56, 'texto' => 22, 'subtexto' => 10],
    'xl' => ['svg' => 200, 'stroke' => 32, 'raio' => 70, 'texto' => 28, 'subtexto' => 12],
    default => ['svg' => 120, 'stroke' => 18, 'raio' => 42, 'texto' => 18, 'subtexto' => 9],
};

$circunferencia = 2 * M_PI * $tamanhos['raio'];
$centro = $tamanhos['svg'] / 2;
$offset = 0;
@endphp

<div {{ $attributes->merge(['class' => 'neo-border shadow-neo p-4 bg-white']) }}>
    @if($rotuloCentro)
        <h3 class="font-heading font-bold uppercase text-lg border-b-4 border-black pb-2 mb-4">{{ $rotuloCentro }}</h3>
    @endif

    <div class="flex flex-col items-center gap-6">
        <svg viewBox="0 0 {{ $tamanhos['svg'] }} {{ $tamanhos['svg'] }}"
             style="height: {{ $tamanho === 'sm' ? '80px' : ($tamanho === 'lg' ? '160px' : '120px') }}; max-width: {{ $tamanho === 'sm' ? '80px' : ($tamanho === 'lg' ? '160px' : '120px') }};"
             class="overflow-visible">

            <circle cx="{{ $centro }}" cy="{{ $centro }}" r="{{ $tamanhos['raio'] }}"
                    fill="none"
                    stroke="#F0EAD6"
                    stroke-width="{{ $tamanhos['stroke'] + 2 }}" />

            <circle cx="{{ $centro }}" cy="{{ $centro }}" r="{{ $tamanhos['raio'] }}"
                    fill="none"
                    stroke="black"
                    stroke-width="{{ $tamanhos['stroke'] + 4 }}"
                    stroke-dasharray="{{ $circunferencia }}"
                    transform="rotate(-90 {{ $centro }} {{ $centro }})" />

            @foreach($dados as $i => $dado)
                @php
                    $cor    = $dado['cor'] ?? ($coresPadrao[$i % count($coresPadrao)]);
                    $share  = $total > 0 ? ($dado['valor'] / $total) : 0;
                    $dash   = $share * $circunferencia;
                    $gap    = $circunferencia - $dash;
                @endphp
                <circle cx="{{ $centro }}" cy="{{ $centro }}" r="{{ $tamanhos['raio'] }}"
                        fill="none"
                        stroke="{{ $cor }}"
                        stroke-width="{{ $tamanhos['stroke'] }}"
                        stroke-dasharray="{{ number_format($dash, 4, '.', '') }} {{ number_format($gap, 4, '.', '') }}"
                        stroke-dashoffset="{{ number_format(-$offset, 4, '.', '') }}"
                        transform="rotate(-90 {{ $centro }} {{ $centro }})" />
                @php $offset += $dash; @endphp
            @endforeach

            <text x="{{ $centro }}" y="{{ $centro + 5 }}"
                  text-anchor="middle"
                  style="font-family: Oswald, sans-serif; font-size: {{ $tamanhos['texto'] }}px; font-weight: 700;">
                {{ $total }}
            </text>
        </svg>

        <div class="flex flex-wrap justify-center gap-3">
            @foreach($dados as $i => $dado)
                @php
                    $cor = $dado['cor'] ?? ($coresPadrao[$i % count($coresPadrao)]);
                    $pct = $total > 0 ? round(($dado['valor'] / $total) * 100) : 0;
                @endphp
                <div class="flex items-center gap-2">
                    <span class="w-4 h-4 neo-border inline-block flex-shrink-0" style="background-color: {{ $cor }};"></span>
                    <span class="font-body text-xs font-bold">{{ $dado['rotulo'] }} ({{ $pct }}%)</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
