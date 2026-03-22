{{--
    ┌─────────────────────────────────────────────────────────────────┐
    │  x-neo.list-item — Item de Lista com Avatar Neo-Brutalista      │
    ├─────────────────────────────────────────────────────────────────┤
    │  DESCRIÇÃO                                                      │
    │  Linha de lista interativa com avatar circular à esquerda,      │
    │  nome e papel/função à direita, e seta de navegação. Utiliza    │
    │  internamente o componente x-neo.avatar.                        │
    │                                                                 │
    │  PROPRIEDADES                                                   │
    │  @iniciais  string  Iniciais exibidas no avatar (ex: "JD")     │
    │  @cor       string  Classe de cor do avatar (ex: bg-neo-purple) │
    │  @nome      string  Nome completo exibido em caixa alta         │
    │  @papel     string  Subtítulo: cargo, função ou descrição       │
    │  @href      string  URL de destino ao clicar (opcional)         │
    │  @tamanho   string  Tamanho do avatar: sm | md | lg             │
    │                                                                 │
    │  USO                                                            │
    │  <ul>                                                           │
    │    <x-neo.list-item                                             │
    │       iniciais="JD" cor="bg-neo-purple"                         │
    │       nome="João Doe" papel="Desenvolvedor"                     │
    │       href="/perfil/joao"                                       │
    │    />                                                           │
    │  </ul>                                                          │
    └─────────────────────────────────────────────────────────────────┘
--}}

@props([
    'iniciais' => '??',
    'cor'      => 'bg-neo-purple',
    'nome'     => '',              
    'papel'    => '',              
    'href'     => null,            
    'tamanho'  => 'md',            
])

<li>
    {{-- Wrapper interativo: funciona como botão/link --}}
    <div
        class="flex items-center justify-between neo-border bg-white p-3 shadow-[--shadow-neo] hover:bg-neo-bg transition-colors cursor-pointer"
        tabindex="0"
        role="button"
        aria-label="Ver detalhes de {{ $nome }}"
        @if($href) onclick="window.location.href='{{ $href }}'" @endif
    >
        <div class="flex items-center gap-4">

            {{-- Avatar com iniciais — delega para x-neo.avatar --}}
            <x-neo.avatar
                :iniciais="$iniciais"
                :cor="$cor"
                :nome="$nome"
                :tamanho="$tamanho"
            />

            {{-- Conteúdo textual --}}
            <div>
                <div class="font-heading font-bold leading-tight">{{ strtoupper($nome) }}</div>
                <div class="text-xs text-gray-500 font-body">{{ $papel }}</div>
            </div>

        </div>

        {{-- Seta indicadora de navegação --}}
        <i class="fa-solid fa-chevron-right text-sm icon-hover" aria-hidden="true"></i>
    </div>
</li>
