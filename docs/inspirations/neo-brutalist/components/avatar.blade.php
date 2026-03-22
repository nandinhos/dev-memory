@props(['src' => null, 'nome' => null, 'tamanho' => 'md', 'cor' => 'teal'])

@php
$size = match($tamanho) {
    'sm'  => 'w-8 h-8 text-xs',
    'md'  => 'w-12 h-12 text-sm',
    'lg'  => 'w-16 h-16 text-base',
    'xl'  => 'w-20 h-20 text-lg',
    default => 'w-12 h-12 text-sm',
};

$bg = match($cor) {
    'yellow'  => 'bg-neo-yellow',
    'magenta' => 'bg-neo-magenta',
    'salmon'  => 'bg-neo-salmon',
    'green'   => 'bg-neo-green',
    'purple'  => 'bg-neo-purple text-white',
    default   => 'bg-neo-teal',
};

$initials = $nome ? strtoupper(substr($nome, 0, 2)) : '?';
@endphp

<div class="neo-border shadow-neo-sm rounded-full overflow-hidden flex items-center justify-center {{ $size }} {{ $bg }} font-heading font-bold">
    @if($src)
        <img src="{{ $src }}" alt="{{ $nome ?? 'Avatar' }}" class="w-full h-full object-cover">
    @else
        {{ $initials }}
    @endif
</div>
