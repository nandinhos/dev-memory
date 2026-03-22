@props(['paginaAtual' => 1, 'totalPaginas' => 1, 'baseUrl' => '?page='])

@if($totalPaginas > 1)
    <nav class="flex justify-center gap-2 flex-wrap" aria-label="Paginação">
        {{-- Previous --}}
        @if($paginaAtual > 1)
            <a href="{{ $baseUrl . ($paginaAtual - 1) }}" class="btn-neo bg-white neo-border shadow-neo px-3 py-1 font-heading font-bold uppercase text-sm" aria-label="Página anterior">
                &laquo;
            </a>
        @endif

        {{-- Pages --}}
        @for($i = 1; $i <= $totalPaginas; $i++)
            <a
                href="{{ $baseUrl . $i }}"
                class="btn-neo {{ $i === $paginaAtual ? 'bg-neo-teal' : 'bg-white' }} neo-border shadow-neo px-3 py-1 font-heading font-bold uppercase text-sm"
                @if($i === $paginaAtual) aria-current="page" @endif
            >
                {{ $i }}
            </a>
        @endfor

        {{-- Next --}}
        @if($paginaAtual < $totalPaginas)
            <a href="{{ $baseUrl . ($paginaAtual + 1) }}" class="btn-neo bg-white neo-border shadow-neo px-3 py-1 font-heading font-bold uppercase text-sm" aria-label="Próxima página">
                &raquo;
            </a>
        @endif
    </nav>
@endif
