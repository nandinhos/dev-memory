@props(['titulo' => 'Neo-Brutalist'])

<header class="bg-white neo-border shadow-neo p-4 neo-animate-slide-in" x-data="{ mobileMenuOpen: false }">
    <div class="flex justify-between items-center">
        <h1 class="font-heading text-xl sm:text-2xl uppercase leading-tight">{{ $titulo }}</h1>

        <div class="flex items-center gap-3">
            {{-- Desktop nav --}}
            @isset($nav)
                <div class="hidden md:block">{{ $nav }}</div>
            @endisset

            {{-- Desktop actions --}}
            @isset($actions)
                <div class="hidden md:flex items-center gap-3">{{ $actions }}</div>
            @endisset

            {{-- Mobile hamburger --}}
            @if(isset($nav) || isset($actions))
                <button
                    class="md:hidden neo-border shadow-neo-sm bg-white p-2 w-10 h-10 flex flex-col justify-center items-center gap-1.5 btn-neo"
                    x-on:click="mobileMenuOpen = !mobileMenuOpen"
                    aria-label="Abrir menu"
                    :aria-expanded="mobileMenuOpen"
                >
                    <span class="block w-5 h-0.5 bg-black transition-all" :class="mobileMenuOpen ? 'rotate-45 translate-y-2' : ''"></span>
                    <span class="block w-5 h-0.5 bg-black transition-all" :class="mobileMenuOpen ? 'opacity-0' : ''"></span>
                    <span class="block w-5 h-0.5 bg-black transition-all" :class="mobileMenuOpen ? '-rotate-45 -translate-y-2' : ''"></span>
                </button>
            @endif
        </div>
    </div>

    {{-- Mobile dropdown menu --}}
    @if(isset($nav) || isset($actions))
        <div
            x-show="mobileMenuOpen"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            x-cloak
            class="md:hidden mt-3 pt-3 border-t-4 border-black flex flex-col gap-3"
        >
            @isset($nav)
                <div class="[&>nav]:flex-col [&>nav>a]:w-full">{{ $nav }}</div>
            @endisset
            @isset($actions)
                <div class="flex items-center gap-3 flex-wrap">{{ $actions }}</div>
            @endisset
        </div>
    @endif
</header>
