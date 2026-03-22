@props([
    'variante' => 'primario',
    'tipo' => 'button',
    'desativado' => false,
    'label' => null,
    'tamanho' => 'md',
])

@php
$sizeClasses = match($tamanho) {
    'sm' => 'px-4 py-1.5 text-xs',
    'md' => 'px-6 py-2 text-sm',
    'lg' => 'px-8 py-3 text-base',
    default => 'px-6 py-2 text-sm',
};

$classes = match($variante) {
    'primario'    => 'btn-neo bg-neo-teal neo-border-sm shadow-neo font-heading hover:bg-neo-yellow transition-colors duration-100',
    'pilula'      => 'btn-neo bg-neo-teal neo-border-sm shadow-neo rounded-full font-heading hover:shadow-neo-lg transition-all duration-100',
    'contorno'    => 'btn-neo bg-neo-white neo-border-sm shadow-neo font-heading hover:bg-neo-yellow transition-colors duration-100',
    'destrutivo'  => 'btn-neo bg-neo-magenta neo-border-sm shadow-neo font-heading -rotate-2 hover:rotate-0 transition-transform duration-100',
    'sucesso'     => 'btn-neo bg-neo-green neo-border-sm shadow-neo font-heading hover:shadow-neo-lg transition-colors duration-100',
    'texto'       => 'underline underline-offset-2 font-heading font-bold px-4 py-2 hover:text-gray-600 transition-colors duration-100',
    default       => 'btn-neo bg-neo-teal neo-border-sm shadow-neo font-heading hover:bg-neo-yellow transition-colors duration-100',
};
@endphp

<button 
    type="{{ $tipo }}" 
    @if($desativado) disabled @endif 
    @if($label) aria-label="{{ $label }}" @endif 
    {{ $attributes->merge(['class' => "$classes $sizeClasses"]) }}
>
    {{ $slot }}
</button>
