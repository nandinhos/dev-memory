{{--
    ┌─────────────────────────────────────────────────────────────────┐
    │  x-neo.breadcrumb — Navegação Secundária Neo-Brutalista         │
    ├─────────────────────────────────────────────────────────────────┤
    │  DESCRIÇÃO                                                      │
    │  Trilha de navegação hierárquica (migalhas de pão) que mostra   │
    │  ao usuário onde ele está na estrutura do site. O último item   │
    │  é sempre a página atual, destacada com fundo amarelo.          │
    │                                                                 │
    │  PROPRIEDADES                                                   │
    │  @itens  array  Lista de itens do breadcrumb. Cada item é um   │
    │                 array com:                                      │
    │                 'label' → texto do link (obrigatório)           │
    │                 'href'  → URL destino (omitir no último item)   │
    │                                                                 │
    │  NOTAS DE ACESSIBILIDADE                                        │
    │  - Usa <nav aria-label="..."> para landmark                     │
    │  - Último item tem aria-current="page"                          │
    │  - Separadores são aria-hidden                                  │
    │                                                                 │
    │  USO                                                            │
    │  <x-neo.breadcrumb :itens="[                                    │
    │      ['label' => 'Início',  'href' => '/'],                     │
    │      ['label' => 'Produtos','href' => '/produtos'],             │
    │      ['label' => 'Camiseta Neo'],                               │
    │  ]" />                                                          │
    └─────────────────────────────────────────────────────────────────┘
--}}

@props(['itens' => []])

<nav aria-label="Caminho de navegação (breadcrumb)" {{ $attributes }}>
    <ol class="flex items-center gap-1 font-bold font-body text-sm flex-wrap">

        @foreach($itens as $index => $item)
            @php
                // O último item é sempre a página atual — sem link
                $ehUltimo = $index === count($itens) - 1;
            @endphp

            <li>
                @if($ehUltimo)
                    {{-- Página atual: destaque amarelo, sem link --}}
                    <span
                        class="bg-neo-yellow border-2 border-black px-1 shadow-[--shadow-neo-sm]"
                        aria-current="page"
                    >{{ strtoupper($item['label']) }}</span>

                @else
                    {{-- Links intermediários: sublinhado com hover --}}
                    <a
                        href="{{ $item['href'] ?? '#' }}"
                        class="underline decoration-2 hover:text-gray-600 transition-colors"
                        aria-label="Ir para {{ $item['label'] }}"
                    >{{ strtoupper($item['label']) }}</a>
                @endif
            </li>

            {{-- Separador ">" entre itens (oculto para leitores de tela) --}}
            @if(!$ehUltimo)
                <li aria-hidden="true">
                    <span class="mx-0.5 text-gray-400">&gt;</span>
                </li>
            @endif
        @endforeach

    </ol>
</nav>
