@props([
    'titulo' => null,
    'subtitulo' => null,
])

<div class="bg-neo-white neo-border shadow-neo p-6 mb-8 animate-fade-in-up">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        @if($titulo)
            <div class="flex items-center gap-4">
                <div>
                    <h1 class="font-heading text-2xl tracking-wide m-0">{{ $titulo }}</h1>
                    @if($subtitulo)
                        <span class="text-xs font-mono text-gray-600">{{ $subtitulo }}</span>
                    @endif
                </div>
            </div>
        @endif
        @if(isset($actions))
            <div class="flex gap-2">
                {{ $actions }}
            </div>
        @endif
    </div>
    @if(isset($breadcrumb))
        <div class="mt-4 pt-4 border-t-2 border-black">
            {{ $breadcrumb }}
        </div>
    @endif
</div>
