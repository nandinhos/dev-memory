@props([
    'tipo' => 'info',
])

@php
[$bg, $iconPath] = match($tipo) {
    'sucesso' => ['bg-neo-green', 'M5 13l4 4L19 7'],
    'aviso'   => ['bg-neo-yellow', 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
    'erro'    => ['bg-neo-magenta', 'M6 18L18 6M6 6l12 12'],
    'info'    => ['bg-neo-teal', 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
    default   => ['bg-neo-teal', 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
};
@endphp

<div class="{{ $bg }} neo-border-sm p-3 font-bold flex items-center gap-3 shadow-neo font-mono feedback-banner" 
     role="{{ $tipo === 'erro' || $tipo === 'sucesso' ? 'alert' : 'status' }}" 
     aria-live="polite">
    <svg class="w-5 h-5 flex-shrink-0 icon-hover" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}" />
    </svg>
    <span>{{ $slot }}</span>
</div>
