@props([
    'titulo' => null,
    'mensagem' => null,
])

<div class="bg-neo-white neo-border shadow-neo p-8 text-center animate-bounce-in">
    <div class="w-16 h-16 mx-auto mb-4 bg-neo-teal neo-border-sm shadow-neo flex items-center justify-center">
        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
    </div>
    <h3 class="font-heading text-xl mb-2">{{ $titulo ?: 'Nenhum resultado' }}</h3>
    @if($mensagem)
        <p class="font-mono text-sm text-gray-600">{{ $mensagem }}</p>
    @endif
    @if(isset($actions))
        <div class="mt-4 flex gap-2 justify-center">
            {{ $actions }}
        </div>
    @endif
</div>
