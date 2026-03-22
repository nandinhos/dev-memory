@props(['titulo' => 'Nenhum resultado', 'mensagem' => null, 'icone' => 'fa-folder-open'])

<div class="bg-white neo-border shadow-neo p-8 text-center neo-animate-fade-in-up">
    <div class="inline-flex items-center justify-center w-16 h-16 bg-neo-teal neo-border shadow-neo-sm rounded-full mb-4 neo-icon-hover">
        <i class="fas {{ $icone }} text-2xl" aria-hidden="true"></i>
    </div>
    <h3 class="font-heading text-xl uppercase mb-2">{{ $titulo }}</h3>
    @if($mensagem)
        <p class="font-body text-gray-600 text-sm">{{ $mensagem }}</p>
    @endif
    @isset($slot)
        <div class="mt-4">{{ $slot }}</div>
    @endisset
</div>
