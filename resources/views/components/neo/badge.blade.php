@props([
    'variante' => 'padrao',
])

@php
$classes = match($variante) {
    'teal'      => 'bg-neo-teal',
    'magenta'   => 'bg-neo-magenta',
    'yellow'    => 'bg-neo-yellow',
    'green'     => 'bg-neo-green',
    'salmon'    => 'bg-neo-salmon',
    'purple'    => 'bg-neo-purple',
    'contorno'  => 'bg-neo-white',
    default     => 'bg-neo-teal',
};
@endphp

<span class="inline-block {{ $classes }} border-2 border-black px-2 py-0.5 text-xs font-bold font-heading badge-hover">
    {{ $slot }}
</span>
