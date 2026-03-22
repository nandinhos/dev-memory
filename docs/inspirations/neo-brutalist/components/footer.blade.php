@props([
    'texto' => '© 2025 Neo-Brutalist Design System',
    'links' => [],
])

<footer {{ $attributes->merge(['class' => 'bg-white neo-border shadow-neo p-4 mt-8 text-center']) }}>
    <p class="font-body text-sm font-bold uppercase tracking-widest">{{ $texto }}</p>

    @if(count($links) > 0)
        <nav class="mt-2 flex flex-wrap justify-center gap-x-2 gap-y-1" aria-label="Links do rodapé">
            @foreach($links as $i => $link)
                @if($i > 0)
                    <span class="font-body text-xs font-bold text-gray-400" aria-hidden="true">|</span>
                @endif
                <a
                    href="{{ $link['url'] ?? '#' }}"
                    class="font-body text-xs font-bold uppercase hover:underline underline-offset-2 transition-colors duration-100 hover:text-gray-600"
                >{{ $link['rotulo'] ?? $link['label'] ?? '' }}</a>
            @endforeach
        </nav>
    @endif

    @if($slot->isNotEmpty())
        <div class="mt-2">{{ $slot }}</div>
    @endif
</footer>
