@props(['variante' => 'padrao', 'texto' => null])

@php
$classes = match($variante) {
    'sucesso' => 'bg-neo-green',
    'aviso'   => 'bg-neo-yellow',
    'erro'    => 'bg-neo-magenta',
    'roxo'    => 'bg-neo-purple text-white',
    'salmon'  => 'bg-neo-salmon',
    default   => 'bg-neo-teal',
};
@endphp

<span class="inline-block {{ $classes }} border-2 border-black px-2 py-0.5 text-xs font-bold font-heading uppercase neo-badge-hover">
    {{ $texto ?: $slot }}
</span>
