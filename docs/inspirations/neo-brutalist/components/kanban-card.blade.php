@props([
    'titulo'      => '',
    'descricao'   => null,
    'status'      => 'todo',
    'prioridade'  => 'media',
    'responsavel' => null,
    'tags'        => [],
])

@php
$corFaixaPrioridade = match($prioridade) {
    'baixa'   => 'bg-neo-green',
    'media'   => 'bg-neo-yellow',
    'alta'    => 'bg-neo-salmon',
    'urgente' => 'bg-neo-magenta',
    default   => 'bg-neo-yellow',
};

$labelPrioridade = match($prioridade) {
    'baixa'   => 'Baixa',
    'media'   => 'Média',
    'alta'    => 'Alta',
    'urgente' => 'Urgente',
    default   => 'Média',
};

$bgStatus = match($status) {
    'todo'         => 'bg-white',
    'em-andamento' => 'bg-neo-yellow',
    'concluido'    => 'bg-neo-green',
    'bloqueado'    => 'bg-neo-magenta',
    default        => 'bg-white',
};

$labelStatus = match($status) {
    'todo'         => 'A fazer',
    'em-andamento' => 'Em andamento',
    'concluido'    => 'Concluído',
    'bloqueado'    => 'Bloqueado',
    default        => 'A fazer',
};

$iniciais = null;
if ($responsavel) {
    $partes = explode(' ', trim($responsavel));
    $iniciais = mb_strtoupper(mb_substr($partes[0], 0, 1));
    if (count($partes) > 1) {
        $iniciais .= mb_strtoupper(mb_substr($partes[count($partes) - 1], 0, 1));
    }
}
@endphp

<div {{ $attributes->merge(['class' => 'bg-white border-2 border-black shadow-neo flex']) }}>
    {{-- Faixa de prioridade --}}
    <div class="w-1.5 flex-shrink-0 {{ $corFaixaPrioridade }}" title="Prioridade: {{ $labelPrioridade }}"></div>

    {{-- Conteúdo do card --}}
    <div class="flex-1 p-3 min-w-0">
        {{-- Cabeçalho: arraste + status --}}
        <div class="flex items-start justify-between gap-2 mb-2">
            {{-- Ícone de arraste --}}
            <span class="text-gray-400 cursor-grab flex-shrink-0 mt-0.5" aria-hidden="true">
                <svg class="w-4 h-4" viewBox="0 0 16 16" fill="currentColor">
                    <circle cx="5" cy="4" r="1.2"/>
                    <circle cx="11" cy="4" r="1.2"/>
                    <circle cx="5" cy="8" r="1.2"/>
                    <circle cx="11" cy="8" r="1.2"/>
                    <circle cx="5" cy="12" r="1.2"/>
                    <circle cx="11" cy="12" r="1.2"/>
                </svg>
            </span>
            {{-- Badge de status --}}
            <span class="inline-block {{ $bgStatus }} border-2 border-black px-2 py-0.5 text-xs font-heading font-bold uppercase flex-shrink-0">
                {{ $labelStatus }}
            </span>
        </div>

        {{-- Título --}}
        <p class="font-heading font-bold text-sm uppercase leading-tight mb-1">{{ $titulo }}</p>

        {{-- Descrição --}}
        @if($descricao)
            <p class="font-body text-xs text-gray-600 leading-snug mb-2">{{ $descricao }}</p>
        @endif

        {{-- Tags --}}
        @if(count($tags) > 0)
            <div class="flex flex-wrap gap-1 mb-2">
                @foreach($tags as $tag)
                    <span class="inline-block bg-white border-2 border-black px-1.5 py-0.5 text-xs font-heading font-bold uppercase">{{ $tag }}</span>
                @endforeach
            </div>
        @endif

        {{-- Rodapé: responsável + prioridade --}}
        <div class="flex items-center justify-between gap-2 mt-2">
            {{-- Responsável --}}
            @if($responsavel)
                <div class="flex items-center gap-1.5">
                    <span
                        class="w-7 h-7 flex items-center justify-center border-2 border-black bg-neo-teal font-heading font-bold text-xs uppercase"
                        title="{{ $responsavel }}"
                    >{{ $iniciais }}</span>
                    <span class="font-body text-xs text-gray-600 truncate max-w-[80px]">{{ $responsavel }}</span>
                </div>
            @else
                <span></span>
            @endif
            {{-- Badge de prioridade --}}
            <span class="inline-block {{ $corFaixaPrioridade }} border-2 border-black px-1.5 py-0.5 text-xs font-heading font-bold uppercase">
                {{ $labelPrioridade }}
            </span>
        </div>
    </div>
</div>
