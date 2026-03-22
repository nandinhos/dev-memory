@props(['variante' => 'primario', 'tipo' => 'button', 'desativado' => false, 'label' => null])

@php
$classes = match($variante) {
    'primario'   => 'btn-neo bg-neo-teal neo-border shadow-neo px-6 py-2 font-heading font-bold uppercase tracking-wide hover:bg-neo-yellow transition-colors duration-100',
    'pilula'     => 'btn-neo bg-neo-teal neo-border shadow-neo rounded-full px-6 py-2 font-heading font-bold uppercase tracking-wide hover:shadow-neo-lg transition-all duration-100',
    'contorno'   => 'btn-neo bg-white neo-border shadow-neo rounded-xl px-6 py-2 font-heading font-bold uppercase tracking-wide hover:bg-neo-yellow transition-colors duration-100',
    'destrutivo' => 'btn-neo bg-neo-magenta neo-border shadow-neo px-6 py-2 font-heading font-bold uppercase tracking-wide -rotate-2 hover:rotate-0 transition-transform duration-100',
    'texto'      => 'underline underline-offset-2 font-heading font-bold uppercase px-4 py-2 hover:text-gray-600 transition-colors duration-100',
    default      => 'btn-neo bg-neo-teal neo-border shadow-neo px-6 py-2 font-heading font-bold uppercase tracking-wide hover:bg-neo-yellow transition-colors duration-100',
};
@endphp

<button
    type="{{ $tipo }}"
    @if($desativado) disabled @endif
    @if($label) aria-label="{{ $label }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    {{ $slot }}
</button>
