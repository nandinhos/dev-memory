@props([
    'mensagem' => '',
    'tipo'     => 'info',
    'duracao'  => 3000,
])

@php
$corFaixa = match($tipo) {
    'sucesso' => 'bg-neo-green',
    'erro'    => 'bg-neo-magenta',
    'aviso'   => 'bg-neo-yellow',
    default   => 'bg-neo-teal',
};

$labelTipo = match($tipo) {
    'sucesso' => 'Sucesso',
    'erro'    => 'Erro',
    'aviso'   => 'Aviso',
    default   => 'Info',
};

$icone = match($tipo) {
    'sucesso' => '✓',
    'erro'    => '✕',
    'aviso'   => '!',
    default   => 'i',
};
@endphp

<div
    x-data="{
        visivel: true,
        init() {
            setTimeout(() => { this.visivel = false; }, {{ $duracao }});
        }
    }"
    x-show="visivel"
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-x-4"
    x-transition:enter-end="opacity-100 translate-x-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-x-0"
    x-transition:leave-end="opacity-0 translate-x-4"
    {{ $attributes->merge(['class' => 'fixed bottom-6 right-6 z-50']) }}
    role="alert"
    aria-live="assertive"
    aria-atomic="true"
>
    <div class="bg-white neo-border shadow-neo-xl w-80 max-w-[calc(100vw-2rem)] flex overflow-hidden">
        {{-- Faixa lateral colorida --}}
        <div class="w-2 flex-shrink-0 {{ $corFaixa }}"></div>

        {{-- Ícone tipo --}}
        <div class="flex items-start pt-4 pl-3 flex-shrink-0">
            <span class="w-6 h-6 flex items-center justify-center {{ $corFaixa }} border-2 border-black font-heading font-bold text-xs">
                {{ $icone }}
            </span>
        </div>

        {{-- Texto --}}
        <div class="flex-1 p-4 pr-3 min-w-0">
            <p class="font-heading font-bold uppercase text-xs mb-1">{{ $labelTipo }}</p>
            <p class="font-body text-sm leading-snug">{{ $mensagem }}</p>
            @if($slot->isNotEmpty())
                <div class="mt-2 font-body text-xs text-gray-600">{{ $slot }}</div>
            @endif
        </div>

        {{-- Botão fechar --}}
        <div class="p-2 flex-shrink-0">
            <button
                @click="visivel = false"
                class="w-6 h-6 flex items-center justify-center border-2 border-black bg-white font-heading font-bold text-sm hover:bg-neo-yellow transition-colors duration-100"
                aria-label="Fechar notificação"
            >
                ×
            </button>
        </div>
    </div>
</div>
