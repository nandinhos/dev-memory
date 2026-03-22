@props([
    'name'   => 'color',
    'label'  => null,
    'colors' => ['#FACC15', '#22D3EE', '#E879F9', '#FDA4AF', '#00FF7F', '#9370DB', '#FF6B35', '#F0EAD6'],
    'value'  => null,
])

@php
$initialColor = $value ?? ($colors[0] ?? '#FACC15');
@endphp

<div
    x-data="{
        selected: '{{ $initialColor }}',
        hex: '{{ $initialColor }}',
        setColor(c) {
            this.selected = c;
            this.hex = c;
        },
        updateFromHex() {
            if (/^#[0-9A-Fa-f]{6}$/.test(this.hex)) {
                this.selected = this.hex;
            }
        }
    }"
    class="w-full space-y-3"
>
    @if($label)
        <label class="block text-xs font-bold font-body uppercase tracking-wider">{{ $label }}</label>
    @endif

    {{-- Swatches --}}
    <div class="flex flex-wrap gap-2" role="group" aria-label="{{ $label ?? 'Color swatches' }}">
        @foreach($colors as $color)
            <button
                type="button"
                @click="setColor('{{ $color }}')"
                :class="selected === '{{ $color }}' ? 'shadow-neo translate-x-[-2px] translate-y-[-2px]' : 'shadow-none'"
                class="w-8 h-8 border-2 border-black transition-all duration-100 flex-shrink-0"
                style="background-color: {{ $color }};"
                aria-label="Select color {{ $color }}"
            ></button>
        @endforeach
    </div>

    {{-- Preview + hex input --}}
    <div class="flex items-stretch gap-3">
        {{-- Color preview box --}}
        <div
            :style="'background-color:' + selected"
            class="w-12 h-10 border-4 border-black shadow-neo flex-shrink-0"
        ></div>

        {{-- Hex input --}}
        <input
            type="text"
            x-model="hex"
            @input="updateFromHex"
            maxlength="7"
            placeholder="#FACC15"
            class="input-neo neo-border shadow-neo px-3 py-2 font-body font-bold text-sm uppercase w-full outline-none bg-white"
            aria-label="Hex color value"
        />
    </div>

    {{-- Hidden form field --}}
    <input type="hidden" name="{{ $name }}" :value="selected" />
</div>
