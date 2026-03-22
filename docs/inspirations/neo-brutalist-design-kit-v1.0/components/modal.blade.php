{{--
    ┌─────────────────────────────────────────────────────────────────┐
    │  x-neo.modal — Modal de Confirmação Neo-Brutalista              │
    ├─────────────────────────────────────────────────────────────────┤
    │  DESCRIÇÃO                                                      │
    │  Diálogo modal interativo gerenciado por Alpine.js. Exibe       │
    │  um overlay semi-transparente e um card de confirmação com      │
    │  cabeçalho, corpo e botões de ação. O componente gerencia       │
    │  seu próprio estado via x-data.                                 │
    │                                                                 │
    │  SLOTS                                                          │
    │  $gatilho   Conteúdo que abre o modal (ex: <x-neo.button>)      │
    │  $slot      Corpo do modal (mensagem/conteúdo)                  │
    │  $acoes     (opcional) Sobrescreve os botões padrão             │
    │                                                                 │
    │  PROPRIEDADES                                                   │
    │  @titulo         string  Título exibido no cabeçalho do modal   │
    │  @textoCancelar  string  Texto do botão de cancelar             │
    │  @textoConfirmar string  Texto do botão de confirmar            │
    │  @id             string  ID único (necessário para aria-*)       │
    │                                                                 │
    │  USO                                                            │
    │  <x-neo.modal titulo="Confirmar Exclusão" id="modal-excluir">   │
    │      <x-slot:gatilho>                                           │
    │          <x-neo.button variante="destrutivo">Excluir</x-neo.button>│
    │      </x-slot:gatilho>                                          │
    │      Tem certeza que deseja excluir este registro?              │
    │  </x-neo.modal>                                                 │
    └─────────────────────────────────────────────────────────────────┘
--}}

@props([
    'titulo'         => 'Confirmar Ação',
    'textoCancelar'  => 'Cancelar',
    'textoConfirmar' => 'Confirmar',
    'id'             => 'modal-' . uniqid(), 
])

{{-- Wrapper Alpine: controla o estado aberto/fechado --}}
<div
    x-data="{ modalAberto: false }"
    @keydown.escape.window="modalAberto = false"
    {{ $attributes }}
>
    {{-- ── Gatilho: abre o modal ao clicar ── --}}
    <div @click.stop="modalAberto = true" role="presentation">
        {{ $gatilho }}
    </div>

    {{-- ── Overlay + Wrapper do Modal ── --}}
    <div
        x-show="modalAberto"
        x-transition.opacity
        @click.self="modalAberto = false"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $id }}-titulo"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
    >
        {{-- Card do modal —  clique interno não fecha --}}
        <div
            class="neo-border bg-white shadow-[--shadow-neo-xl] max-w-sm w-full"
            @click.stop
        >

            {{-- Cabeçalho: fundo preto com título e botão fechar --}}
            <div class="bg-neo-black text-white p-3 font-bold flex justify-between items-center">
                <span id="{{ $id }}-titulo" class="font-heading text-base">{{ $titulo }}</span>
                <button
                    type="button"
                    @click.stop="modalAberto = false"
                    class="hover:text-neo-yellow transition-colors cursor-pointer"
                    aria-label="Fechar modal"
                >
                    <i class="fa-solid fa-xmark text-lg icon-hover"></i>
                </button>
            </div>

            {{-- Corpo: slot de conteúdo customizável --}}
            <div class="p-5">
                <div class="text-sm mb-5 font-body leading-relaxed">
                    {{ $slot }}
                </div>

                {{-- Ações: slot customizável ou botões padrão --}}
                @if(isset($acoes) && $acoes->isNotEmpty())
                    {{ $acoes }}
                @else
                    <div class="flex gap-3 justify-end">
                        {{-- Cancelar --}}
                        <button
                            type="button"
                            @click.stop="modalAberto = false"
                            class="btn-neo bg-white border-4 border-black px-4 py-2 font-bold text-sm font-body hover:bg-gray-100"
                        >{{ $textoCancelar }}</button>

                        {{-- Confirmar --}}
                        <button
                            type="button"
                            @click.stop="modalAberto = false"
                            class="btn-neo bg-neo-teal border-4 border-black px-4 py-2 font-bold text-sm shadow-[--shadow-neo-sm] font-body hover:bg-neo-yellow"
                        >{{ $textoConfirmar }}</button>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>
