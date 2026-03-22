@props(['itens' => []])

<nav class="font-body text-sm" aria-label="Breadcrumb">
    <ol class="flex flex-wrap items-center gap-2">
        @foreach($itens as $index => $item)
            @if($index > 0)
                <li class="text-gray-500 font-bold select-none">/</li>
            @endif

            <li>
                @if(is_array($item) && isset($item[1]))
                    <a href="{{ $item[1] }}" class="hover:text-neo-magenta transition-colors font-bold underline underline-offset-2">
                        {{ $item[0] }}
                    </a>
                @else
                    <span class="font-bold" aria-current="page">{{ is_array($item) ? $item[0] : $item }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
