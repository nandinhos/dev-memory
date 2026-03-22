@props([
    'name'  => 'range',
    'label' => null,
    'min'   => 0,
    'max'   => 100,
    'step'  => 1,
    'value' => 50,
])

<div
    x-data="{ val: {{ $value }} }"
    class="w-full space-y-2"
>
    @if($label)
        <div class="flex items-center justify-between gap-3">
            <label for="{{ $name }}" class="block text-xs font-bold font-body uppercase tracking-wider">{{ $label }}</label>

            {{-- Brutalist value badge --}}
            <span
                x-text="val"
                class="inline-block bg-neo-yellow border-2 border-black shadow-neo-sm px-3 py-0.5 text-sm font-heading font-bold min-w-[3rem] text-center"
            ></span>
        </div>
    @endif

    <div class="relative flex items-center">
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="range"
            min="{{ $min }}"
            max="{{ $max }}"
            step="{{ $step }}"
            x-model="val"
            {{ $attributes }}
            style="
                -webkit-appearance: none;
                appearance: none;
                width: 100%;
                height: 8px;
                background: #000;
                border: 3px solid #000;
                outline: none;
                cursor: pointer;
            "
            class="neo-range"
        />
    </div>

    <style>
        .neo-range::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 22px;
            height: 22px;
            background: #FACC15;
            border: 3px solid #000;
            box-shadow: 3px 3px 0 #000;
            cursor: pointer;
            border-radius: 0;
        }
        .neo-range::-moz-range-thumb {
            width: 22px;
            height: 22px;
            background: #FACC15;
            border: 3px solid #000;
            box-shadow: 3px 3px 0 #000;
            cursor: pointer;
            border-radius: 0;
        }
        .neo-range::-webkit-slider-thumb:active {
            transform: translate(3px, 3px);
            box-shadow: none;
        }
    </style>
</div>
