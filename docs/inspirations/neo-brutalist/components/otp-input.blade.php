@props([
    'name'   => 'otp',
    'label'  => 'Verification Code',
    'length' => 6,
])

<div
    x-data="{
        digits: Array({{ $length }}).fill(''),
        get combined() { return this.digits.join(''); },
        onKey(e, index) {
            const key = e.key;
            if (key === 'Backspace') {
                if (this.digits[index] !== '') {
                    this.digits[index] = '';
                } else if (index > 0) {
                    this.digits[index - 1] = '';
                    this.$nextTick(() => this.$refs['digit' + (index - 1)].focus());
                }
                return;
            }
            if (key === 'ArrowLeft' && index > 0) {
                this.$refs['digit' + (index - 1)].focus();
                return;
            }
            if (key === 'ArrowRight' && index < {{ $length - 1 }}) {
                this.$refs['digit' + (index + 1)].focus();
                return;
            }
            if (!/^[0-9]$/.test(key)) { return; }
            this.digits[index] = key;
            if (index < {{ $length - 1 }}) {
                this.$nextTick(() => this.$refs['digit' + (index + 1)].focus());
            }
        },
        onPaste(e) {
            const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, {{ $length }});
            pasted.split('').forEach((char, i) => { this.digits[i] = char; });
            const nextIndex = Math.min(pasted.length, {{ $length - 1 }});
            this.$nextTick(() => this.$refs['digit' + nextIndex].focus());
            e.preventDefault();
        }
    }"
    class="w-full space-y-3"
>
    <label class="block text-xs font-bold font-body uppercase tracking-wider">{{ $label }}</label>

    <div class="flex items-center gap-2">
        @for($i = 0; $i < $length; $i++)
            <input
                x-ref="digit{{ $i }}"
                type="text"
                inputmode="numeric"
                maxlength="1"
                :value="digits[{{ $i }}]"
                @keydown="onKey($event, {{ $i }})"
                @paste="onPaste($event)"
                @focus="$event.target.select()"
                class="w-11 h-14 border-4 border-black shadow-neo text-center font-heading font-bold text-2xl bg-white outline-none focus:bg-neo-yellow focus:shadow-neo-lg transition-all duration-100"
                aria-label="Digit {{ $i + 1 }} of {{ $length }}"
                autocomplete="one-time-code"
            />
            @if($i === 2 && $length >= 6)
                <span class="font-heading font-bold text-2xl select-none">–</span>
            @endif
        @endfor
    </div>

    {{-- Hidden combined field for form submission --}}
    <input type="hidden" name="{{ $name }}" :value="combined" />
</div>
