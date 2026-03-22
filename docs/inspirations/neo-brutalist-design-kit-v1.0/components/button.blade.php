{{--
    ┌─────────────────────────────────────────────────────────────────┐
    │  x-neo.button — Componente de Botão Neo-Brutalista              │
    ├─────────────────────────────────────────────────────────────────┤
    │  DESCRIÇÃO                                                      │
    │  Botão reutilizável com 5 variantes visuais seguindo o          │
    │  design system Neo-Brutalista: bordas grossas, sombra dura      │
    │  e tipografia em caixa alta.                                    │
    │                                                                 │
    │  PROPRIEDADES                                                   │
    │  @variante  string  Estilo visual do botão:                     │
    │             'primario'   → fundo teal, borda grossa             │
    │             'pilula'     → fundo teal, bordas arredondadas      │
    │             'contorno'   → fundo branco, bordas arredondadas    │
    │             'destrutivo' → fundo magenta, leve rotação          │
    │             'texto'      → sem fundo/borda, apenas sublinhado   │
    │  @tipo      string  Atributo type do HTML (button/submit/reset)  │
    │  @desativado boolean Estado desabilitado do botão               │
    │  @label     string  Texto alternativo (aria-label)              │
    │                                                                 │
    │  USO                                                            │
    │  <x-neo.button variante="primario">Salvar</x-neo.button>        │
    │  <x-neo.button variante="destrutivo">Excluir</x-neo.button>     │
    │  <x-neo.button variante="texto">Cancelar</x-neo.button>         │
    └─────────────────────────────────────────────────────────────────┘
--}}

@props([
    'variante'   => 'primario',
    'tipo'       => 'button',
    'desativado' => false,
    'label'      => null,
])


@php
// Mapeamento de variante para classes Tailwind/Neo-Brutalistas
$classes = match($variante) {
    'primario'   => 'btn-neo bg-neo-teal   neo-border shadow-[--shadow-neo] px-6 py-2 font-heading hover:bg-neo-yellow transition-colors duration-100',
    'pilula'     => 'btn-neo bg-neo-teal   neo-border shadow-[--shadow-neo] rounded-full px-6 py-2 font-heading hover:shadow-[--shadow-neo-lg] transition-all duration-100',
    'contorno'   => 'btn-neo bg-white      neo-border shadow-[--shadow-neo] rounded-xl px-6 py-2 font-heading hover:bg-neo-yellow transition-colors duration-100',
    'destrutivo' => 'btn-neo bg-neo-magenta neo-border shadow-[--shadow-neo] px-6 py-2 font-heading -rotate-2 hover:rotate-0 transition-transform duration-100',
    'texto'      => 'underline underline-offset-2 font-heading font-bold px-4 py-2 hover:text-gray-600 transition-colors duration-100',
    default      => 'btn-neo bg-neo-teal   neo-border shadow-[--shadow-neo] px-6 py-2 font-heading hover:bg-neo-yellow transition-colors duration-100',
};
@endphp


<button
    type="{{ $tipo }}"
    @if($desativado) disabled aria-disabled="true" @endif
    @if($label) aria-label="{{ $label }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    {{ $slot }}
</button>
