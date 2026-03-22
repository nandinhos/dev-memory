@props([
    'id'          => null,
    'rotulo'      => null,
    'opcoes'      => [],        // array de strings OU [['valor' => '', 'label' => '', 'icone' => '']]
    'selecionado' => null,
    'placeholder' => 'Selecionar...',
    'erro'        => null,
    'multiplo'    => false,
])

@php
// Normaliza opcoes para sempre ser [['valor'=>..., 'label'=>...]]
$opcaoNormalizada = collect($opcoes)->map(function ($op) {
    return is_array($op)
        ? ['valor' => $op['valor'] ?? $op[0] ?? '', 'label' => $op['label'] ?? $op[0] ?? '', 'icone' => $op['icone'] ?? null]
        : ['valor' => $op, 'label' => $op, 'icone' => null];
})->toArray();
@endphp

<div
    x-data="{
        aberto: false,
        selecionado: {{ $selecionado ? "'$selecionado'" : 'null' }},
        opcoes: {{ Js::from($opcaoNormalizada) }},
        get label() {
            if (!this.selecionado) return null;
            const op = this.opcoes.find(o => o.valor === this.selecionado);
            return op ? op.label : null;
        },
        selecionar(valor) { this.selecionado = valor; this.aberto = false; },
        fechar(e) { if (!this.$el.contains(e.relatedTarget)) this.aberto = false; }
    }"
    class="relative w-full"
    @keydown.escape="aberto = false"
    @focusout="fechar($event)"
>
    @if($rotulo)
        <label for="{{ $id }}-btn" class="block text-xs font-bold font-body uppercase tracking-wider mb-1">
            {{ $rotulo }}
        </label>
    @endif

    {{-- Trigger button --}}
    <button
        id="{{ $id }}-btn"
        type="button"
        role="combobox"
        :aria-expanded="aberto"
        aria-haspopup="listbox"
        @click="aberto = !aberto"
        class="w-full flex items-center justify-between px-3 py-2 font-body text-sm bg-white neo-border transition-all duration-100 text-left"
        :class="aberto
            ? 'shadow-none translate-x-[4px] translate-y-[4px] border-neo-magenta'
            : '{{ $erro ? 'border-red-500 shadow-[4px_4px_0_#ef4444]' : 'shadow-neo hover:border-neo-magenta' }}'"
    >
        <span :class="selecionado ? 'text-black font-bold' : 'text-gray-400'">
            <span x-text="label || '{{ $placeholder }}'"></span>
        </span>
        <span
            class="ml-2 transition-transform duration-200 flex-shrink-0"
            :class="aberto ? 'rotate-180' : 'rotate-0'"
            aria-hidden="true"
        >
            <svg class="w-4 h-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <polyline points="4,6 8,10 12,6"/>
            </svg>
        </span>
    </button>

    {{-- Hidden real input --}}
    <input type="hidden" id="{{ $id }}" :value="selecionado" />

    {{-- Dropdown list --}}
    <div
        x-show="aberto"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="absolute z-40 w-full mt-1 bg-white neo-border shadow-neo-lg overflow-hidden"
        role="listbox"
    >
        @foreach($opcaoNormalizada as $opcao)
            <button
                type="button"
                role="option"
                :aria-selected="selecionado === '{{ $opcao['valor'] }}'"
                @click="selecionar('{{ $opcao['valor'] }}')"
                class="w-full flex items-center gap-2 px-3 py-2 text-left font-body text-sm border-b-2 border-black last:border-b-0 transition-colors duration-75"
                :class="selecionado === '{{ $opcao['valor'] }}'
                    ? 'bg-neo-teal font-bold'
                    : 'hover:bg-neo-yellow'"
            >
                @if($opcao['icone'])
                    <i class="fas {{ $opcao['icone'] }} w-4 text-center" aria-hidden="true"></i>
                @endif
                <span>{{ $opcao['label'] }}</span>
                <span
                    x-show="selecionado === '{{ $opcao['valor'] }}'"
                    class="ml-auto"
                    aria-hidden="true"
                >
                    <svg class="w-4 h-4" viewBox="0 0 16 16" fill="none" stroke="black" stroke-width="2.5" stroke-linecap="round">
                        <polyline points="3,8 7,12 13,4"/>
                    </svg>
                </span>
            </button>
        @endforeach
    </div>

    @if($erro)
        <p class="text-red-500 text-xs font-body font-bold mt-1">{{ $erro }}</p>
    @endif
</div>
