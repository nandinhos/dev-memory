{{--
    ┌─────────────────────────────────────────────────────────────────┐
    │  x-neo.empty-state — Estado Vazio Neo-Brutalista                │
    ├─────────────────────────────────────────────────────────────────┤
    │  DESCRIÇÃO                                                      │
    │  Componente para exibir quando uma lista ou coleção estiver     │
    │  vazia. Apresenta ícone, título, descrição e botão de ação      │
    │  (CTA) para orientar o usuário ao próximo passo.                │
    │                                                                 │
    │  PROPRIEDADES                                                   │
    │  @titulo     string  Título principal exibido em caixa alta     │
    │  @descricao  string  Texto explicativo abaixo do título         │
    │  @icone      string  Classe Font Awesome (ex: 'fas fa-folder')  │
    │  @cta        string  Texto do botão de ação chamada (opcional)  │
    │  @href       string  URL destino do botão CTA                   │
    │                                                                 │
    │  SLOT CUSTOMIZADO                                               │
    │  Se o slot padrão for preenchido, substitui o botão automático. │
    │  Use para ações mais complexas.                                 │
    │                                                                 │
    │  USO SIMPLES                                                    │
    │  <x-neo.empty-state                                             │
    │      titulo="Sem resultados"                                    │
    │      descricao="Sua pesquisa não retornou itens."               │
    │      cta="Nova Busca" href="/busca"                             │
    │  />                                                             │
    │                                                                 │
    │  USO COM SLOT CUSTOMIZADO                                       │
    │  <x-neo.empty-state titulo="Vazio">                             │
    │      <x-neo.button variante="primario">Criar</x-neo.button>     │
    │  </x-neo.empty-state>                                           │
    └─────────────────────────────────────────────────────────────────┘
--}}

@props([
    'titulo'    => 'Nenhum item ainda',
    'descricao' => null,
    'icone'     => 'fas fa-box-open',
    'cta'       => null,
    'href'      => '#',
])

<div
    {{ $attributes->merge(['class' => 'neo-border bg-white p-6 text-center flex flex-col items-center shadow-[--shadow-neo]']) }}
    role="status"
    aria-label="{{ $titulo }}"
>

    {{-- Ícone centralizado dentro de círculo com borda Neo-Brutalista --}}
    <div
        class="w-14 h-14 rounded-full border-4 border-black flex items-center justify-center mb-3 text-2xl bg-gray-100"
        aria-hidden="true"
    >
        <i class="{{ $icone }}"></i>
    </div>

    {{-- Título em Oswald, caixa alta --}}
    <h4 class="font-heading text-xl mb-1">{{ strtoupper($titulo) }}</h4>

    {{-- Descrição em Space Mono, tom cinza --}}
    @if($descricao)
        <p class="font-body text-xs text-gray-500 mb-4 max-w-xs leading-relaxed">
            {{ $descricao }}
        </p>
    @endif

    {{-- Ação: slot customizado tem prioridade sobre botão automático --}}
    @if($slot->isNotEmpty())
        {{ $slot }}
    @elseif($cta)
        <a href="{{ $href }}">
            <x-neo.button variante="primario" :label="$cta">
                {{ strtoupper($cta) }}
            </x-neo.button>
        </a>
    @endif

</div>
