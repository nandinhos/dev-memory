@props([
    'id' => null,
    'tipo' => 'text',
    'placeholder' => '',
    'rotulo' => null,
    'erro' => null,
    'valor' => null,
])

@php
    // Gera um id estável quando não informado, para o label for= associar de
    // verdade (acessibilidade). Detecta qualquer variante de wire:model
    // (wire:model.live, .blur, .debounce…) para o id não virar aleatório.
    if ($id === null) {
        $wireKey = collect($attributes->getAttributes())->keys()
            ->first(fn ($k) => str_starts_with($k, 'wire:model'));
        $id = 'neo-input-'.($wireKey ? $attributes->get($wireKey) : \Illuminate\Support\Str::random(6));
    }
@endphp

<div class="space-y-1 w-full">
    @if($rotulo)
        <label for="{{ $id }}" class="block text-xs font-bold font-mono uppercase tracking-wider">
            {{ $rotulo }}
        </label>
    @else
        <label for="{{ $id }}" class="sr-only">{{ $rotulo ?: ($placeholder ?: $id) }}</label>
    @endif
    
    <div class="relative">
        <input
            id="{{ $id }}"
            type="{{ $tipo }}"
            placeholder="{{ $placeholder }}"
            @if($valor) value="{{ $valor }}" @endif
            @if($erro) aria-invalid="true" aria-describedby="{{ $id }}-erro" @endif
            {{ $attributes->merge([
                'class' => $erro
                    ? 'w-full border-4 border-red-500 bg-red-50 text-red-600 shadow-neo px-3 py-2 outline-none font-mono'
                    : 'input-neo w-full neo-border-sm shadow-neo px-3 py-2 outline-none font-mono bg-neo-white'
            ]) }}
        />
        @if($erro)
            <span id="{{ $id }}-erro" class="absolute -top-3 right-0 bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 border-2 border-black font-mono" role="alert">ERRO</span>
        @endif
    </div>
    @if($erro)
        <p class="text-red-600 text-xs font-mono font-bold">{{ $erro }}</p>
    @endif
</div>
