{{--
    ┌─────────────────────────────────────────────────────────────────┐
    │  x-neo.card — Card genérico Neo-Brutalista                      │
    ├─────────────────────────────────────────────────────────────────┤
    │  DESCRIÇÃO                                                      │
    │  Container de conteúdo com bordas grossas, sombra dura e        │
    │  suporte a 3 zonas independentes via slots do Laravel Blade:    │
    │  topo (imagem/mídia), corpo (default) e rodapé (ações).         │
    │                                                                 │
    │  SLOTS                                                          │
    │  $imagem    (opcional) Substitui o placeholder cinza no topo    │
    │  $slot      Conteúdo principal do corpo do card                 │
    │  $rodape    (opcional) Área de ações/botões no rodapé           │
    │                                                                 │
    │  PROPRIEDADES                                                   │
    │  @titulo    string  Título principal do card (H3)               │
    │  @descricao string  Parágrafo descritivo abaixo do título       │
    │                                                                 │
    │  USO SIMPLES                                                    │
    │  <x-neo.card titulo="Produto X" descricao="Descrição...">       │
    │      <x-slot:rodape>                                            │
    │          <x-neo.button variante="primario">Ver</x-neo.button>   │
    │      </x-slot:rodape>                                           │
    │  </x-neo.card>                                                  │
    │                                                                 │
    │  USO COM IMAGEM CUSTOMIZADA                                     │
    │  <x-neo.card titulo="Foto">                                     │
    │      <x-slot:imagem><img src="..." /></x-slot:imagem>           │
    │  </x-neo.card>                                                  │
    └─────────────────────────────────────────────────────────────────┘
--}}

@props([
    'titulo'    => null,
    'descricao' => null,
])

<article
    {{ $attributes->merge(['class' => 'neo-border bg-white shadow-[--shadow-neo] card-neo overflow-hidden']) }}
    aria-label="{{ $titulo ?? 'Card de conteúdo' }}"
>

    {{-- ── Zona de Imagem/Mídia (topo) ── --}}
    @if(isset($imagem) && $imagem->isNotEmpty())
        {{-- Slot customizado: usa a imagem fornecida --}}
        <div class="border-b-4 border-black">{{ $imagem }}</div>
    @else
        {{-- Placeholder padrão quando nenhuma imagem é passada --}}
        <div
            class="bg-gray-200 h-32 flex items-center justify-center border-b-4 border-black"
            aria-hidden="true"
        >
            <i class="fa-regular fa-image text-5xl opacity-40 icon-hover"></i>
        </div>
    @endif

    {{-- ── Corpo do Card ── --}}
    <div class="p-6">

        {{-- Título semântico H3 --}}
        @if($titulo)
            <h3 class="font-heading text-2xl mb-2">{{ $titulo }}</h3>
        @endif

        {{-- Descrição em fonte monospace (Space Mono) --}}
        @if($descricao)
            <p class="text-sm mb-4 leading-relaxed font-body text-gray-700">{{ $descricao }}</p>
        @endif

        {{-- Slot de conteúdo customizado (substitui ou complementa descricao) --}}
        {{ $slot }}

        {{-- Zona de ações/rodapé --}}
        @if(isset($rodape) && $rodape->isNotEmpty())
            <div class="mt-4">{{ $rodape }}</div>
        @endif

    </div>
</article>
