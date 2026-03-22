{{--
    ┌─────────────────────────────────────────────────────────────────┐
    │  x-neo.avatar — Avatar circular com iniciais Neo-Brutalista     │
    ├─────────────────────────────────────────────────────────────────┤
    │  DESCRIÇÃO                                                      │
    │  Representação visual de um usuário/entidade por meio de        │
    │  iniciais dentro de um círculo colorido com borda preta         │
    │  e sombra dura (estilo Neo-Brutalista).                         │
    │                                                                 │
    │  PROPRIEDADES                                                   │
    │  @iniciais  string  Texto exibido (ex: "JD", "AS")             │
    │  @cor       string  Cor de fundo — qualquer classe Tailwind     │
    │                     Ex: 'bg-neo-purple', 'bg-neo-salmon'        │
    │  @tamanho   string  Dimensão do avatar:                         │
    │             'sm' → 32px  |  'md' → 40px  |  'lg' → 48px        │
    │  @nome      string  Nome completo p/ aria-label (acessibilidade) │
    │                                                                 │
    │  USO                                                            │
    │  <x-neo.avatar iniciais="JD" cor="bg-neo-purple" nome="João" /> │
    │  <x-neo.avatar iniciais="AS" cor="bg-neo-salmon" tamanho="lg"/> │
    └─────────────────────────────────────────────────────────────────┘
--}}

@props([
    'iniciais' => '??',
    'cor'      => 'bg-neo-purple', 
    'tamanho'  => 'md',           
    'nome'     => null,           
])

@php
// Mapeia tamanho para classes de dimensão e borda
$dimensoes = match($tamanho) {
    'sm'    => 'w-8  h-8  text-xs  border-2',
    'md'    => 'w-10 h-10 text-sm  border-2',
    'lg'    => 'w-12 h-12 text-base border-4',
    default => 'w-10 h-10 text-sm  border-2',
};
// aria-label descritivo quando o nome é conhecido
$ariaLabel = $nome ? "Avatar de {$nome}" : "Avatar {$iniciais}";
@endphp

<div
    class="rounded-full {{ $cor }} {{ $dimensoes }} border-black flex items-center justify-center font-bold font-body shadow-[3px_3px_0_#000] shrink-0"
    style="color: {{ str_contains($cor, 'purple') || str_contains($cor, 'black') ? 'white' : 'black' }}"
    aria-label="{{ $ariaLabel }}"
    {{ $attributes }}
>
    {{ $iniciais }}
</div>
