@props([
    'classe' => '',
])

<div class="card-neo bg-neo-white neo-border shadow-neo p-4 {{ $classe }}">
    {{ $slot }}
</div>
