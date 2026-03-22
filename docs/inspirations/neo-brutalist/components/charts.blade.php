@props([
    'tipo'   => 'bar',
    'dados'  => [],
    'altura' => '200px',
    'titulo' => null,
])

@php
$coresNeo = ['bg-neo-teal', 'bg-neo-yellow', 'bg-neo-magenta', 'bg-neo-green', 'bg-neo-purple', 'bg-neo-salmon'];
$coresHex = ['#22D3EE', '#FDE047', '#E879F9', '#4ADE80', '#A855F7', '#FB923C'];

$maxValor = collect($dados)->max('valor') ?: 1;
@endphp

<div {{ $attributes->merge(['class' => 'bg-white neo-border shadow-neo p-4']) }}>
    @if($titulo)
        <h3 class="font-heading font-bold uppercase text-lg border-b-4 border-black pb-2 mb-4">{{ $titulo }}</h3>
    @endif

    @if($tipo === 'bar')
        {{-- Gráfico de barras --}}
        <div class="flex items-end justify-around gap-2" style="height: {{ $altura }}">
            @foreach($dados as $i => $dado)
                @php
                    $percentual = $maxValor > 0 ? ($dado['valor'] / $maxValor) * 100 : 0;
                    $corBg = !empty($dado['cor']) ? '' : $coresNeo[$i % count($coresNeo)];
                    $corStyle = !empty($dado['cor']) ? "background-color: {$dado['cor']};" : '';
                @endphp
                <div class="flex flex-col items-center flex-1 h-full justify-end">
                    {{-- Valor acima da barra --}}
                    <span class="font-heading font-bold text-xs mb-1">{{ $dado['valor'] }}</span>
                    {{-- Barra --}}
                    <div
                        class="{{ $corBg }} neo-border shadow-neo w-full transition-all duration-300"
                        style="height: {{ $percentual }}%; min-height: 4px; {{ $corStyle }}"
                    ></div>
                    {{-- Rótulo abaixo --}}
                    <span class="font-body text-xs mt-1 text-center truncate w-full" title="{{ $dado['rotulo'] ?? '' }}">{{ $dado['rotulo'] ?? '' }}</span>
                </div>
            @endforeach
        </div>

    @elseif($tipo === 'donut')
        {{-- Gráfico de donut via SVG --}}
        @php
            $total = collect($dados)->sum('valor') ?: 1;
            $raio = 60;
            $circunferencia = 2 * M_PI * $raio;
            $offset = 0;
            $segmentos = [];
            foreach ($dados as $i => $dado) {
                $porcao = ($dado['valor'] / $total) * $circunferencia;
                $segmentos[] = [
                    'comprimento' => $porcao,
                    'inicio'      => $offset,
                    'cor'         => !empty($dado['cor']) ? $dado['cor'] : $coresHex[$i % count($coresHex)],
                    'rotulo'      => $dado['rotulo'] ?? '',
                    'valor'       => $dado['valor'],
                ];
                $offset += $porcao;
            }
        @endphp
        <div class="flex flex-col items-center gap-4">
            <svg viewBox="0 0 160 160" style="height: {{ $altura }}; max-width: {{ $altura }};" class="overflow-visible">
                {{-- Trilha de fundo --}}
                <circle
                    cx="80" cy="80" r="{{ $raio }}"
                    fill="none"
                    stroke="#F0EAD6"
                    stroke-width="28"
                />
                {{-- Borda preta externa --}}
                <circle
                    cx="80" cy="80" r="{{ $raio }}"
                    fill="none"
                    stroke="black"
                    stroke-width="30"
                    stroke-dasharray="{{ $circunferencia }}"
                    transform="rotate(-90 80 80)"
                />
                {{-- Segmentos coloridos --}}
                @foreach($segmentos as $seg)
                    <circle
                        cx="80" cy="80" r="{{ $raio }}"
                        fill="none"
                        stroke="{{ $seg['cor'] }}"
                        stroke-width="26"
                        stroke-dasharray="{{ $seg['comprimento'] }} {{ $circunferencia - $seg['comprimento'] }}"
                        stroke-dashoffset="{{ -$seg['inicio'] }}"
                        transform="rotate(-90 80 80)"
                    />
                @endforeach
                {{-- Total no centro --}}
                <text x="80" y="85" text-anchor="middle" class="font-heading" style="font-family: Oswald, sans-serif; font-size: 22px; font-weight: 700;">
                    {{ collect($dados)->sum('valor') }}
                </text>
            </svg>
            {{-- Legenda --}}
            <div class="flex flex-wrap justify-center gap-2">
                @foreach($segmentos as $seg)
                    <div class="flex items-center gap-1">
                        <span class="w-3 h-3 neo-border inline-block flex-shrink-0" style="background-color: {{ $seg['cor'] }};"></span>
                        <span class="font-body text-xs font-bold">{{ $seg['rotulo'] }} ({{ $seg['valor'] }})</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
