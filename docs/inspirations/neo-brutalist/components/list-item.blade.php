@props(['href' => '#', 'ativo' => false])

<a
    href="{{ $href }}"
    class="block p-3 border-b-2 border-black font-body hover:bg-neo-bg neo-list-hover transition-colors {{ $ativo ? 'bg-neo-yellow font-bold' : '' }}"
    @if($ativo) aria-current="true" @endif
>
    {{ $slot }}
</a>
