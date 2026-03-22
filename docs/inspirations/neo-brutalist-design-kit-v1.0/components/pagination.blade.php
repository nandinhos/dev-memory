{{--
    ┌─────────────────────────────────────────────────────────────────┐
    │  x-neo.pagination — Paginação Neo-Brutalista                    │
    ├─────────────────────────────────────────────────────────────────┤
    │  DESCRIÇÃO                                                      │
    │  Controle de paginação com navegação anterior/próxima,          │
    │  números de página e reticências para intervalos grandes.       │
    │  A página ativa fica com fundo preto (inversão de cores).       │
    │                                                                 │
    │  PROPRIEDADES                                                   │
    │  @pagina   int  Número da página atualmente ativa (base 1)      │
    │  @total    int  Total de páginas disponíveis                    │
    │  @janela   int  Quantas páginas mostrar antes das reticências   │
    │                                                                 │
    │  EVENTOS                                                        │
    │  Os botões emitem onclick para integração com Livewire/Alpine    │
    │  via $attributes. Passe wire:click ou @click conforme necessário.│
    │                                                                 │
    │  USO                                                            │
    │  {{-- Estático / de demonstração --}}                           │
    │  <x-neo.pagination :pagina="1" :total="10" />                   │
    │                                                                 │
    │  {{-- Integrado com Livewire --}}                               │
    │  {{-- <x-neo.pagination :pagina="$page" :total="$totalPages" --}}│
    │  {{--     wire:click="setPage($page)" />                        --}}
    └─────────────────────────────────────────────────────────────────┘
--}}

@props([
    'pagina' => 1,   
    'total'  => 10,  
    'janela' => 3,   
])

<nav aria-label="Paginação de resultados" {{ $attributes }}>
    <ol class="flex flex-wrap gap-1 font-bold font-body text-sm items-center">

        {{-- Botão "Anterior" — desabilitado na primeira página --}}
        <li>
            <button
                type="button"
                class="border-4 border-black px-2 py-0.5 bg-white shadow-[--shadow-neo-sm] hover:bg-neo-teal transition-colors {{ $pagina <= 1 ? 'opacity-40 cursor-not-allowed' : '' }}"
                aria-label="Página anterior"
                @if($pagina <= 1) disabled @endif
            >&lt; PREV</button>
        </li>

        {{-- Números de página dentro da "janela" --}}
        @for($i = 1; $i <= min($janela, $total); $i++)
            <li>
                <button
                    type="button"
                    class="{{ $i === $pagina
                        ? 'bg-neo-black text-white'
                        : 'bg-white hover:bg-neo-teal transition-colors'
                    }} border-4 border-black px-2 py-0.5 shadow-[--shadow-neo-sm]"
                    aria-label="Página {{ $i }}"
                    @if($i === $pagina) aria-current="page" @endif
                >{{ $i }}</button>
            </li>
        @endfor

        {{-- Reticências + última página (quando há mais que a janela) --}}
        @if($total > $janela)
            <li aria-hidden="true">
                <span class="px-1 font-body text-gray-400">...</span>
            </li>
            <li>
                <button
                    type="button"
                    class="border-4 border-black px-2 py-0.5 {{ $pagina === $total ? 'bg-neo-black text-white' : 'bg-white hover:bg-neo-teal transition-colors' }} shadow-[--shadow-neo-sm]"
                    aria-label="Página {{ $total }}"
                    @if($pagina === $total) aria-current="page" @endif
                >{{ $total }}</button>
            </li>
        @endif

        {{-- Botão "Próximo" — desabilitado na última página --}}
        <li>
            <button
                type="button"
                class="border-4 border-black px-2 py-0.5 bg-white shadow-[--shadow-neo-sm] hover:bg-neo-teal transition-colors {{ $pagina >= $total ? 'opacity-40 cursor-not-allowed' : '' }}"
                aria-label="Próxima página"
                @if($pagina >= $total) disabled @endif
            >NEXT &gt;</button>
        </li>

    </ol>
</nav>
