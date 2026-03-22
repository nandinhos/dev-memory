@props(['itens' => [], 'ativo' => null])

<nav class="flex flex-wrap gap-1" aria-label="Navegação principal">
    @foreach($itens as $item)
        @php
            $isAtivo = isset($item['ativo']) ? $item['ativo'] : ($ativo === ($item['label'] ?? ''));
        @endphp
        <a
            href="{{ $item['href'] ?? '#' }}"
            class="btn-neo {{ $isAtivo ? 'bg-neo-teal' : 'bg-white' }} neo-border shadow-neo-sm px-4 py-1.5 font-heading font-bold uppercase text-sm hover:bg-neo-yellow transition-colors"
            @if($isAtivo) aria-current="page" @endif
        >
            @if(isset($item['icone']))
                <i class="fas {{ $item['icone'] }} mr-1" aria-hidden="true"></i>
            @endif
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>
