@props(['titulo' => null, 'abrir' => false, 'tamanho' => 'md'])

@php
$width = match($tamanho) {
    'sm' => 'max-w-sm',
    'md' => 'max-w-md',
    'lg' => 'max-w-lg',
    'xl' => 'max-w-xl',
    default => 'max-w-md',
};
@endphp

<div x-data="{ aberto: {{ $abrir ? 'true' : 'false' }} }">
    @isset($trigger)
        <div @click="aberto = true">{{ $trigger }}</div>
    @endisset

    <div x-show="aberto" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
        {{-- Backdrop --}}
        <div
            x-show="aberto"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-black/60"
            @click="aberto = false"
        ></div>

        {{-- Dialog --}}
        <div
            x-show="aberto"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative {{ $width }} w-full mx-4"
            role="dialog"
            aria-modal="true"
        >
            <div class="bg-white neo-border shadow-neo-xl p-6">
                @if($titulo)
                    <h2 class="font-heading text-2xl uppercase mb-4 border-b-4 border-black pb-3">{{ $titulo }}</h2>
                @endif

                <div class="font-body">
                    {{ $slot }}
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    @isset($footer)
                        {{ $footer }}
                    @else
                        <button
                            @click="aberto = false"
                            class="btn-neo bg-neo-teal neo-border shadow-neo px-5 py-2 font-heading font-bold uppercase"
                        >Fechar</button>
                    @endisset
                </div>
            </div>
        </div>
    </div>
</div>
