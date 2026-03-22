@props([
    'id' => null,
    'rotulo' => null,
    'erro' => null,
    'placeholder' => 'Selecione...',
])

<div class="space-y-1 w-full">
    @if($rotulo)
        <label for="{{ $id }}" class="block text-xs font-bold font-mono uppercase tracking-wider">
            {{ $rotulo }}
        </label>
    @elseif($id)
        <label for="{{ $id }}" class="sr-only">{{ $placeholder }}</label>
    @endif
    
    <div class="relative">
        <select
            id="{{ $id }}"
            @if($erro) aria-invalid="true" @endif
            {{ $attributes->merge([
                'class' => $erro
                    ? 'w-full border-4 border-red-500 bg-red-50 text-red-600 shadow-neo px-3 py-2 outline-none font-mono cursor-pointer'
                    : 'input-neo w-full neo-border-sm shadow-neo px-3 py-2 outline-none font-mono bg-neo-white cursor-pointer appearance-none'
            ]) }}
        >
            <option value="">{{ $placeholder }}</option>
            {{ $slot }}
        </select>
        <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </div>
    </div>
    @if($erro)
        <p class="text-red-600 text-xs font-mono font-bold">{{ $erro }}</p>
    @endif
</div>
