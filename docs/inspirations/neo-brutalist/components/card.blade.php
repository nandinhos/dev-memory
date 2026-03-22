@props(['classe' => '', 'cor' => 'white'])

@php
$bg = match($cor) {
    'teal'    => 'bg-neo-teal',
    'yellow'  => 'bg-neo-yellow',
    'magenta' => 'bg-neo-magenta',
    'salmon'  => 'bg-neo-salmon',
    'green'   => 'bg-neo-green',
    'purple'  => 'bg-neo-purple',
    default   => 'bg-white',
};
@endphp

<div class="card-neo {{ $bg }} neo-border shadow-neo p-4 {{ $classe }}">
    {{ $slot }}
</div>
