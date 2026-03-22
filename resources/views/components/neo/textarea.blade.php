@props([
    'id' => null,
    'rotulo' => null,
    'erro' => null,
    'placeholder' => '',
    'rows' => 4,
])

<div class="space-y-1 w-full">
    @if($rotulo)
        <label for="{{ $id }}" class="block text-xs font-bold font-mono uppercase tracking-wider">
            {{ $rotulo }}
        </label>
    @elseif($id)
        <label for="{{ $id }}" class="sr-only">{{ $placeholder }}</label>
    @endif
    
    <textarea
        id="{{ $id }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        @if($erro) aria-invalid="true" aria-describedby="{{ $id }}-erro" @endif
        {{ $attributes->merge([
            'class' => $erro
                ? 'w-full border-4 border-red-500 bg-red-50 text-red-600 shadow-neo px-3 py-2 outline-none font-mono resize-y'
                : 'input-neo w-full neo-border-sm shadow-neo px-3 py-2 outline-none font-mono bg-neo-white resize-y'
        ]) }}
    >{{ $attributes->get('value', '') }}</textarea>
    @if($erro)
        <p class="text-red-600 text-xs font-mono font-bold">{{ $erro }}</p>
    @endif
</div>
