@props([
    'memoria' => null,
])

@php
$typeColors = [
    'error' => 'bg-neo-magenta',
    'lesson' => 'bg-neo-yellow',
    'best_practice' => 'bg-neo-green',
];

$scopeColors = [
    'project' => 'bg-neo-teal',
    'global' => 'bg-neo-purple',
];

// Cor semântica por status de validação. Pendente = LARANJA em evidência
// (precisa de ação humana); validado = verde; demais = neutro.
$statusStyles = [
    'pending'    => ['badge' => 'bg-neo-orange text-black', 'header' => 'bg-[#FDBA74]', 'headerText' => 'text-black', 'label' => 'text-neo-orange'],
    'validated'  => ['badge' => 'bg-neo-green text-black',  'header' => 'bg-[#E0E7FF]', 'headerText' => 'text-gray-500', 'label' => 'text-neo-green'],
    'rejected'   => ['badge' => 'bg-gray-400 text-black',   'header' => 'bg-[#E5E7EB]', 'headerText' => 'text-gray-500', 'label' => 'text-gray-500'],
    'superseded' => ['badge' => 'bg-gray-400 text-black',   'header' => 'bg-[#E5E7EB]', 'headerText' => 'text-gray-500', 'label' => 'text-gray-500'],
];

$typeBg = $typeColors[$memoria->type->value] ?? 'bg-neo-teal';
$scopeBg = $scopeColors[$memoria->scope->value] ?? 'bg-neo-teal';
$ss = $statusStyles[$memoria->validation_status->value] ?? $statusStyles['rejected'];
@endphp

<div class="block card-neo bg-neo-white neo-border shadow-neo hover:shadow-neo-lg transition-all duration-150 group relative overflow-hidden">

    {{-- Header: fundo semântico (laranja p/ pendente = pede atenção) --}}
    <div class="h-10 {{ $ss['header'] }} border-b-2 border-black flex items-center px-4 justify-between">
        <span class="text-[10px] font-mono {{ $ss['headerText'] }} font-bold tracking-wider">
            MEM_ID: {{ str_pad($memoria->id, 4, '0', STR_PAD_LEFT) }}
        </span>
        <div class="flex items-center gap-1.5">
            {{-- Badge de status em cor semântica (laranja/verde/cinza) --}}
            <span class="{{ $ss['badge'] }} border-2 border-black px-2 py-0.5 text-[10px] font-black uppercase tracking-tighter">
                {{ $memoria->validation_status->label() }}
            </span>
            {{-- Resultado da checagem no Context7, ao lado do status humano: são eixos
                 DIFERENTES (um é curadoria, outro é confronto com a documentação oficial).
                 Ausente = memória ainda não passou pela checagem. --}}
            @if($memoria->doc_validation_status)
                <span class="{{ $memoria->doc_validation_status->badgeClasses() }} border-2 border-black px-2 py-0.5 text-[10px] font-black uppercase tracking-tighter"
                      title="Checagem na documentação oficial: {{ $memoria->doc_validation_status->label() }}">
                    {{ $memoria->doc_validation_status->shortLabel() }}
                </span>
            @endif
        </div>
    </div>

    <div class="p-5">
        <div class="flex items-start justify-between gap-4 mb-3">
            <h3 class="font-heading text-lg leading-tight group-hover:text-neo-magenta transition-colors flex-1">
                <a href="{{ route('memories.show', $memoria) }}" class="hover:underline underline-offset-4 decoration-2">
                    {{ $memoria->title }}
                </a>
            </h3>

            <div class="relative shrink-0">
                <div class="absolute -top-4 -right-2 flex flex-col items-center gap-1">
                    @if($memoria->stack)
                        <span class="bg-black text-white px-2 py-1 text-[10px] font-bold font-heading rotate-3 shadow-neo-sm whitespace-nowrap">
                            {{ Str::limit($memoria->stack, 12) }}
                        </span>
                    @endif
                    <span class="{{ $typeBg }} border-2 border-black px-2 py-1 text-[10px] font-bold font-heading -rotate-2 shadow-neo-sm">
                        {{ $memoria->recurrence_count }}x
                    </span>
                </div>
            </div>
        </div>

        <p class="font-mono text-sm text-gray-700 mb-4 line-clamp-2">
            {{ Str::limit(strip_tags($memoria->description), 180) }}
        </p>

        <div class="flex flex-wrap gap-1">
            <span class="inline-block {{ $typeBg }} border-2 border-black px-2 py-0.5 text-[10px] font-bold font-heading">
                {{ $memoria->type->label() }}
            </span>
            <span class="inline-block {{ $scopeBg }} border-2 border-black px-2 py-0.5 text-[10px] font-bold font-heading">
                {{ $memoria->scope->label() }}
            </span>
        </div>

        {{-- Botão "Promover p/ Global" em Azul Vibrante (#60A5FA) --}}
        @if($memoria->scope->value !== 'global')
            <div class="mt-4">
                <button
                    type="button"
                    x-data
                    @click="$dispatch('promote-memory', { id: '{{ $memoria->id }}' })"
                    class="w-full bg-[#60A5FA] text-black border-2 border-black px-4 py-2 font-black uppercase text-xs shadow-[4px_4px_0px_0px_#000] active:translate-x-[2px] active:translate-y-[2px] active:shadow-none transition-all"
                >
                    Promover p/ Global
                </button>
            </div>
        @endif
    </div>

    {{-- Separador animado "VALIDATED ARCHITECT ///" (Caution Scroll) condicional ao status 'validated' --}}
    @if($memoria->validation_status->value === 'validated')
        <div class="caution-scroll-container">
            <div class="caution-scroll-text">
                VALIDATED ARCHITECT /// VALIDATED ARCHITECT /// VALIDATED ARCHITECT /// VALIDATED ARCHITECT /// VALIDATED ARCHITECT ///
            </div>
        </div>
    @else
        <div class="mx-5 border-t-2 border-black/15"></div>
    @endif

    {{-- Rodapé com labels de "Validação" (Verde) e "Urgência" (Vermelho) em tipografia 900 --}}
    <div class="px-5 py-3 flex items-center justify-between bg-gray-50/50">
        <div class="flex gap-4">
            <div class="flex flex-col">
                <span class="text-[8px] uppercase font-bold text-gray-400 leading-none">Validação</span>
                <span class="text-[10px] font-black {{ $ss['label'] }} uppercase">
                    {{ $memoria->validation_status->label() }}
                </span>
            </div>
            <div class="flex flex-col">
                <span class="text-[8px] uppercase font-bold text-gray-400 leading-none">Urgência</span>
                <span class="text-[10px] font-black text-neo-magenta uppercase">
                    {{ $memoria->type->value === 'error' ? 'ALTA' : 'NORMAL' }}
                </span>
            </div>
        </div>
        
        <a href="{{ route('memories.show', $memoria) }}" class="text-[10px] font-mono text-gray-400 group-hover:text-neo-magenta transition-colors font-bold uppercase tracking-widest">
            Ver →
        </a>
    </div>
</div>
