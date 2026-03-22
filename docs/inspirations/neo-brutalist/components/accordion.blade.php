@props([
    'itens'     => [],      // [['titulo' => '', 'conteudo' => '', 'aberto' => false, 'icone' => ''], ...]
    'exclusivo' => true,    // true = só um aberto por vez
    'cor'       => 'teal',  // cor do header ativo
])

@php
$corAtiva = match($cor) {
    'yellow'  => 'bg-neo-yellow',
    'magenta' => 'bg-neo-magenta',
    'green'   => 'bg-neo-green',
    'purple'  => 'bg-neo-purple',
    default   => 'bg-neo-teal',
};

$abertoPadrao = collect($itens)->search(fn($i) => !empty($i['aberto']));
$abertoPadrao = $abertoPadrao !== false ? $abertoPadrao : -1;
@endphp

<div
    x-data="{ aberto: {{ $abertoPadrao }} }"
    class="w-full neo-border shadow-neo overflow-hidden"
>
    @foreach($itens as $index => $item)
        <div class="{{ $index > 0 ? 'border-t-4 border-black' : '' }}">
            {{-- Header --}}
            <button
                type="button"
                :aria-expanded="aberto === {{ $index }}"
                aria-controls="accordion-panel-{{ $index }}"
                @click="aberto = aberto === {{ $index }} ? -1 : {{ $index }}"
                class="w-full flex items-center justify-between px-4 py-3 font-heading font-bold uppercase text-sm text-left transition-colors duration-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-black"
                :class="aberto === {{ $index }} ? '{{ $corAtiva }}' : 'bg-white hover:bg-neo-yellow'"
            >
                <span class="flex items-center gap-2">
                    @if(!empty($item['icone']))
                        <i class="fas {{ $item['icone'] }}" aria-hidden="true"></i>
                    @endif
                    {{ $item['titulo'] }}
                </span>

                {{-- Chevron --}}
                <span
                    class="flex-shrink-0 ml-4 transition-transform duration-200 border-2 border-black w-6 h-6 flex items-center justify-center"
                    :class="aberto === {{ $index }} ? 'rotate-180 bg-black text-white' : 'rotate-0 bg-transparent'"
                    aria-hidden="true"
                >
                    <svg class="w-3 h-3" viewBox="0 0 12 12" fill="none"
                        :stroke="aberto === {{ $index }} ? 'white' : 'black'"
                        stroke-width="2.5" stroke-linecap="round">
                        <polyline points="2,4 6,8 10,4"/>
                    </svg>
                </span>
            </button>

            {{-- Panel --}}
            <div
                id="accordion-panel-{{ $index }}"
                x-show="aberto === {{ $index }}"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2"
                class="border-t-2 border-black bg-neo-bg px-4 py-4 font-body text-sm"
            >
                {!! $item['conteudo'] !!}
            </div>
        </div>
    @endforeach
</div>
