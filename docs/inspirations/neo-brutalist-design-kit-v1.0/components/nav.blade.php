{{--
    x-neo.nav
    Navegação semântica principal do playground Neo-Brutalista.
    Usa Alpine.js (bundled no Livewire v4) para o menu mobile.
--}}
<nav
    aria-label="Navegação principal"
    x-data="{ aberta: false }"
    class="max-w-7xl mx-auto mb-10"
>
    <div class="neo-border bg-neo-black text-white shadow-hard flex items-stretch justify-between">

        {{-- Marca / Logo --}}
        <a
            href="#"
            aria-label="Início — Sistema de Design Neo-Brutalista"
            class="flex items-center gap-3 px-5 py-3 border-r-4 border-white hover:bg-neo-teal hover:text-black transition-colors duration-100"
        >
            <span class="font-heading text-xl tracking-wider">NB/DS</span>
        </a>

        {{-- Links desktop --}}
        <ul class="hidden md:flex items-stretch" role="list">
            <li>
                <a
                    href="#fundacoes"
                    class="flex items-center px-5 py-3 font-heading text-sm tracking-widest border-r-4 border-white/30 hover:bg-neo-teal hover:text-black hover:border-neo-teal transition-colors duration-100"
                >
                    Fundações
                </a>
            </li>
            <li>
                <a
                    href="#exibicao"
                    class="flex items-center px-5 py-3 font-heading text-sm tracking-widest border-r-4 border-white/30 hover:bg-neo-yellow hover:text-black hover:border-neo-yellow transition-colors duration-100"
                >
                    Exibição
                </a>
            </li>
            <li>
                <a
                    href="#componentes"
                    class="flex items-center px-5 py-3 font-heading text-sm tracking-widest border-r-4 border-white/30 hover:bg-neo-salmon hover:text-black hover:border-neo-salmon transition-colors duration-100"
                >
                    Componentes
                </a>
            </li>
            <li>
                <a
                    href="#feedback"
                    class="flex items-center px-5 py-3 font-heading text-sm tracking-widest hover:bg-neo-purple hover:text-white hover:border-neo-purple transition-colors duration-100"
                >
                    Feedback
                </a>
            </li>
        </ul>

        {{-- Badge versão --}}
        <div class="hidden md:flex items-center px-5 border-l-4 border-white/30">
            <span class="font-body text-xs uppercase tracking-wider text-white/60">v1.0</span>
        </div>

        {{-- Botão hambúrguer (mobile) --}}
        <button
            @click="aberta = !aberta"
            :aria-expanded="aberta"
            aria-controls="menu-mobile"
            aria-label="Abrir menu de navegação"
            class="md:hidden flex items-center justify-center px-5 border-l-4 border-white/30 hover:bg-neo-teal hover:text-black transition-colors duration-100"
        >
            <i class="fa-solid text-lg icon-hover" :class="aberta ? 'fa-xmark' : 'fa-bars'"></i>
        </button>
    </div>

    {{-- Menu mobile --}}
    <div
        id="menu-mobile"
        x-show="aberta"
        x-collapse
        class="md:hidden neo-border border-t-0 bg-neo-black text-white"
    >
        <ul role="list">
            <li>
                <a
                    @click="aberta = false"
                    href="#fundacoes"
                    class="flex items-center gap-3 px-5 py-4 font-heading text-sm tracking-widest border-b-2 border-white/20 hover:bg-neo-teal hover:text-black transition-colors"
                >
                    <i class="fa-solid fa-layer-group w-5 icon-hover"></i> Fundações
                </a>
            </li>
            <li>
                <a
                    @click="aberta = false"
                    href="#exibicao"
                    class="flex items-center gap-3 px-5 py-4 font-heading text-sm tracking-widest border-b-2 border-white/20 hover:bg-neo-yellow hover:text-black transition-colors"
                >
                    <i class="fa-solid fa-table w-5 icon-hover"></i> Exibição de Dados
                </a>
            </li>
            <li>
                <a
                    @click="aberta = false"
                    href="#componentes"
                    class="flex items-center gap-3 px-5 py-4 font-heading text-sm tracking-widest border-b-2 border-white/20 hover:bg-neo-salmon hover:text-black transition-colors"
                >
                    <i class="fa-solid fa-puzzle-piece w-5 icon-hover"></i> Componentes
                </a>
            </li>
            <li>
                <a
                    @click="aberta = false"
                    href="#feedback"
                    class="flex items-center gap-3 px-5 py-4 font-heading text-sm tracking-widest hover:bg-neo-purple hover:text-white transition-colors"
                >
                    <i class="fa-solid fa-bell w-5 icon-hover"></i> Feedback
                </a>
            </li>
        </ul>
    </div>
</nav>
