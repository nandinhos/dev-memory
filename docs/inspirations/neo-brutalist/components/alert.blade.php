@props(['tipo' => 'info'])

@php
[$bg, $icone] = match($tipo) {
    'sucesso' => ['bg-neo-green', 'fa-check-circle'],
    'aviso'   => ['bg-neo-yellow', 'fa-exclamation-triangle'],
    'erro'    => ['bg-neo-magenta', 'fa-times-circle'],
    'info'    => ['bg-neo-teal', 'fa-info-circle'],
    default   => ['bg-neo-teal', 'fa-info-circle'],
};
@endphp

<div
    class="{{ $bg }} neo-border shadow-neo p-3 font-bold flex items-center gap-3 font-body feedback-banner"
    role="{{ in_array($tipo, ['erro', 'sucesso']) ? 'alert' : 'status' }}"
    aria-live="polite"
>
    <i class="fas {{ $icone }} neo-icon-hover text-lg" aria-hidden="true"></i>
    <span>{{ $slot }}</span>
</div>
