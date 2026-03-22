@props([
    'id'          => null,
    'rotulo'      => null,
    'placeholder' => '',
    'erro'        => null,
    'valor'       => null,
    'linhas'      => 4,
    'maxCaracteres' => null,
])

<div class="space-y-1 w-full">
    @if($rotulo)
        <div class="flex justify-between items-baseline">
            <label for="{{ $id }}" class="block text-xs font-bold font-body uppercase tracking-wider">
                {{ $rotulo }}
            </label>
            @if($maxCaracteres)
                <span class="text-xs font-body text-gray-400" x-text="'0 / {{ $maxCaracteres }}'"></span>
            @endif
        </div>
    @elseif($id)
        <label for="{{ $id }}" class="sr-only">{{ $placeholder ?: $id }}</label>
    @endif

    <div class="relative">
        <textarea
            id="{{ $id }}"
            rows="{{ $linhas }}"
            placeholder="{{ $placeholder }}"
            @if($maxCaracteres)
                maxlength="{{ $maxCaracteres }}"
                x-data="{ chars: 0 }"
                @input="$el.closest('.space-y-1').querySelector('span[x-text]') &&
                    ($el.closest('.space-y-1').querySelector('span').textContent = $el.value.length + ' / {{ $maxCaracteres }}')"
            @endif
            @if($erro) aria-invalid="true" aria-describedby="{{ $id }}-erro" @endif
            {{ $attributes->merge([
                'class' => $erro
                    ? 'w-full border-4 border-red-500 bg-red-50 text-red-600 shadow-[4px_4px_0_#ef4444] px-3 py-2 outline-none font-body text-sm resize-y'
                    : 'input-neo w-full neo-border shadow-neo px-3 py-2 outline-none font-body text-sm bg-white resize-y'
            ]) }}
        >{{ $valor }}</textarea>

        @if($erro)
            <span
                id="{{ $id }}-erro"
                class="absolute -top-3 right-0 bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 border-2 border-black font-body"
                role="alert"
            >ERRO</span>
        @endif
    </div>

    @if($erro)
        <p class="text-red-500 text-xs font-body font-bold">{{ $erro }}</p>
    @endif
</div>
