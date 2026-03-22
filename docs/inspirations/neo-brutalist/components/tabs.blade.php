@props([
    'abas'  => [],     // [['id' => '', 'label' => '', 'icone' => ''], ...]
    'ativa' => null,   // id da aba ativa por padrão (default = primeira)
    'cor'   => 'teal', // cor do indicador ativo
])

@php
$corAtiva = match($cor) {
    'yellow'  => 'bg-neo-yellow',
    'magenta' => 'bg-neo-magenta',
    'green'   => 'bg-neo-green',
    'purple'  => 'bg-neo-purple',
    default   => 'bg-neo-teal',
};
$primeira = $ativa ?? ($abas[0]['id'] ?? null);
@endphp

{{--
    Usage:
    <x-neo.tabs :abas="[['id'=>'a','label'=>'A']]" ativa="a">
        <div x-show="ativa === 'a'" class="bg-white neo-border border-t-0 shadow-neo p-5">Content A</div>
    </x-neo.tabs>
--}}

<div
    x-data="{ ativa: '{{ $primeira }}' }"
    class="w-full"
>
    {{-- Tab Bar --}}
    <div class="flex items-end gap-0 border-b-4 border-black overflow-x-auto" role="tablist">
        @foreach($abas as $aba)
            <button
                type="button"
                role="tab"
                :aria-selected="ativa === '{{ $aba['id'] }}'"
                id="tab-{{ $aba['id'] }}"
                aria-controls="panel-{{ $aba['id'] }}"
                @click="ativa = '{{ $aba['id'] }}'"
                class="relative flex items-center gap-2 px-5 py-2.5 font-heading font-bold uppercase text-sm border-t-4 border-l-4 border-r-4 border-black transition-all duration-100 -mb-[4px] whitespace-nowrap focus:outline-none focus-visible:ring-2 focus-visible:ring-black focus-visible:ring-offset-2"
                :class="ativa === '{{ $aba['id'] }}'
                    ? '{{ $corAtiva }} shadow-none translate-y-0 z-10'
                    : 'bg-white shadow-[0_-2px_0_#000] hover:bg-neo-yellow opacity-70 hover:opacity-100'"
            >
                @if(!empty($aba['icone']))
                    <i class="fas {{ $aba['icone'] }}" aria-hidden="true"></i>
                @endif
                {{ $aba['label'] }}

                {{-- Active indicator bar --}}
                <span
                    x-show="ativa === '{{ $aba['id'] }}'"
                    class="absolute bottom-0 left-0 right-0 h-1 bg-black"
                    aria-hidden="true"
                ></span>
            </button>
        @endforeach

        {{-- Filler --}}
        <div class="flex-1 min-w-4" aria-hidden="true"></div>
    </div>

    {{-- Slot: user provides panels with x-show="ativa === 'id'" --}}
    {{ $slot }}
</div>
