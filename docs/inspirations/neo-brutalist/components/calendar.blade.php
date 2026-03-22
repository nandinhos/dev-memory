@props([
    'mes'           => null,
    'ano'           => null,
    'dados'         => [],
    'selecionavel'  => false,
    'mostrarMeses'  => true,
    'lang'          => 'pt-BR',
])

@php
    $mesAtual  = $mes ?? (int) date('n');
    $anoAtual  = $ano ?? (int) date('Y');

    $mesesPt = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    $diasPt   = ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'];

    $nomesMeses = $mesesPt;
    $nomesDias  = $diasPt;

    $primeiroDia = mktime(0, 0, 0, $mesAtual, 1, $anoAtual);
    $diasNoMes   = date('t', $primeiroDia);
    $diaSemana   = date('w', $primeiroDia);

    $eventosPorDia = [];
    foreach ($dados as $evento) {
        $data = $evento['data'] ?? '';
        $dia  = (int) date('j', strtotime($data));
        if (!isset($eventosPorDia[$dia])) {
            $eventosPorDia[$dia] = [];
        }
        $eventosPorDia[$dia][] = $evento;
    }
@endphp

<div x-data="{
    mes: {{ $mesAtual }},
    ano: {{ $anoAtual }},
}" {{ $attributes->merge(['class' => 'neo-border shadow-neo p-4 bg-white']) }}>

    @if($mostrarMeses)
    <div class="flex items-center justify-between mb-4 pb-2 border-b-4 border-black">
        <button @click="mes = mes === 1 ? 12 : mes - 1; if(mes === 12) ano--;"
                class="font-heading font-bold uppercase text-lg hover:text-neo-teal transition-colors">
            ←
        </button>
        <h3 class="font-heading font-bold uppercase text-lg" x-text="`{{ $nomesMeses[$mesAtual - 1] }} {{ $anoAtual }}`">
            {{ $nomesMeses[$mesAtual - 1] }} {{ $anoAtual }}
        </h3>
        <button @click="mes = mes === 12 ? 1 : mes + 1; if(mes === 1) ano++;"
                class="font-heading font-bold uppercase text-lg hover:text-neo-teal transition-colors">
            →
        </button>
    </div>
    @endif

    <div class="grid grid-cols-7 gap-1 mb-2">
        @foreach($nomesDias as $dia)
            <div class="text-center font-heading font-bold uppercase text-xs text-black/50 py-2 border-b-2 border-black">
                {{ $dia }}
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-7 gap-1">
        @for($i = 0; $i < $diaSemana; $i++)
            <div class="aspect-square"></div>
        @endfor

        @for($dia = 1; $dia <= $diasNoMes; $dia++)
            @php
                $eventosDia = $eventosPorDia[$dia] ?? [];
                $temEventos = count($eventosDia) > 0;
                $ehHoje     = ($dia === (int) date('j') && $mesAtual === (int) date('n') && $anoAtual === (int) date('Y'));
            @endphp
            <div class="aspect-square neo-border shadow-neo flex flex-col items-center justify-center relative
                        @if($temEventos) bg-neo-yellow @endif
                        @if($ehHoje) bg-neo-teal @endif
                        hover:shadow-none hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-neo transition-all">
                <span class="font-heading font-bold text-sm text-black">{{ $dia }}</span>
                @if($temEventos)
                    <span class="absolute bottom-0.5 w-1.5 h-1.5 bg-black"></span>
                @endif
            </div>
        @endfor
    </div>

    @if(count($dados) > 0)
    <div class="mt-4 pt-2 border-t-4 border-black">
        <div class="flex flex-wrap gap-2">
            @foreach($dados as $evento)
                <span class="font-heading font-bold uppercase text-xs px-2 py-1 neo-border shadow-neo bg-white">
                    {{ $evento['rotulo'] ?? '' }}
                </span>
            @endforeach
        </div>
    </div>
    @endif
</div>
