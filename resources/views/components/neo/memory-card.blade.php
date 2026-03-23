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

$statusColors = [
    'pending' => 'bg-neo-salmon',
    'validated' => 'bg-neo-green',
    'rejected' => 'bg-gray-400',
];

$typeBg = $typeColors[$memoria->type->value] ?? 'bg-neo-teal';
$scopeBg = $scopeColors[$memoria->scope->value] ?? 'bg-neo-teal';
$statusBg = $statusColors[$memoria->validation_status->value] ?? 'bg-gray-400';
@endphp

<div class="block card-neo bg-neo-white neo-border shadow-neo hover:shadow-neo-lg transition-all duration-150 group relative overflow-hidden">
    
    {{-- Header com fundo Azul Pastel (#E0E7FF) --}}
    <div class="h-10 bg-[#E0E7FF] border-b-2 border-black flex items-center px-4 justify-between">
        <span class="text-[10px] font-mono text-gray-500 font-bold tracking-wider">
            MEM_ID: {{ str_pad($memoria->id, 4, '0', STR_PAD_LEFT) }}
        </span>
        {{-- Badge Roxo (#6366F1) com texto branco e borda preta --}}
        <span class="bg-[#6366F1] text-white border-2 border-black px-2 py-0.5 text-[10px] font-black uppercase tracking-tighter">
            {{ $memoria->validation_status->label() }}
        </span>
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
                    onclick="window.location.href='{{ route('memories.show', $memoria) }}'"
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
                <span class="text-[10px] font-black text-neo-green uppercase">
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
