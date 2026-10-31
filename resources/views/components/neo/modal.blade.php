@props([
    'model' => 'open',
    'titulo' => 'Confirmação',
    'variante' => 'padrao',
    'largura' => 'max-w-md',
])

@php
$headerBg = match($variante) {
    'destrutivo' => 'bg-neo-magenta',
    'aviso'      => 'bg-neo-yellow',
    'sucesso'    => 'bg-neo-green',
    default      => 'bg-neo-teal',
};
@endphp

<div x-show="{{ $model }}" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     @keydown.escape.window="{{ $model }} = false"
     role="dialog" aria-modal="true">
    
    <!-- Backdrop Neo-Brutalista com blur sutil -->
    <div class="fixed inset-0 bg-black/60 backdrop-blur-[2px]"
         x-show="{{ $model }}"
         x-transition:enter="ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="{{ $model }} = false"></div>

    <!-- Caixa Neo-Brutalista do Modal -->
    <div class="relative bg-neo-white neo-border shadow-neo-lg {{ $largura }} w-full z-10 overflow-hidden"
         x-show="{{ $model }}"
         x-transition:enter="ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-2"
         @click.stop>
        
        <!-- Header estilo Neo-Brutalist Banner -->
        <div class="{{ $headerBg }} border-b-4 border-black px-5 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 bg-black inline-block"></span>
                <h3 class="font-heading text-lg font-bold text-black tracking-wider m-0 uppercase">
                    {{ $titulo }}
                </h3>
            </div>
            <button type="button" @click="{{ $model }} = false"
                    aria-label="Fechar"
                    class="w-7 h-7 flex items-center justify-center bg-neo-white neo-border-sm shadow-neo-sm font-mono font-bold text-sm text-black hover:bg-black hover:text-white transition-colors cursor-pointer">
                ✕
            </button>
        </div>

        <!-- Conteúdo do Modal -->
        <div class="p-6">
            {{ $slot }}

            @if(isset($actions))
                <div class="mt-6 pt-4 border-t-2 border-black/10 flex flex-col-reverse sm:flex-row justify-end gap-3">
                    {{ $actions }}
                </div>
            @endif
        </div>
    </div>
</div>
