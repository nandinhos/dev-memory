<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>{{ $title ?? 'Dev Memory' }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sistema de Memória Técnica e Lições Aprendidas">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="bg-neo-bg min-h-screen flex overflow-hidden" x-data="{ sidebarOpen: false }"
      @keydown.escape.window="sidebarOpen = false">

    <!-- Backdrop (apenas mobile/tablet, quando o drawer está aberto) -->
    <div x-show="sidebarOpen" x-cloak x-transition.opacity
         @click="sidebarOpen = false"
         class="fixed inset-0 bg-black/50 z-30 lg:hidden"></div>

    <!-- Sidebar (drawer em <lg, estática em lg+) -->
    <aside id="sidebar-nav" @click="sidebarOpen = false"
           class="sidebar-bege w-72 flex-shrink-0 flex flex-col h-screen fixed lg:relative inset-y-0 left-0 -translate-x-full lg:translate-x-0 transition-transform duration-300 z-40 lg:z-20"
           :class="{ 'translate-x-0': sidebarOpen }">
        <div class="logo-block">
            <a href="{{ route('dashboard') }}" class="no-underline">
                <span class="logo-text"><span class="logo-dev">DEV</span><span class="logo-memory">-MEMORY</span></span>
            </a>
        </div>

        <nav class="flex-1 overflow-y-auto p-6 space-y-4">
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-4 py-3 font-heading font-black text-lg no-underline transition-all border-4 border-transparent hover:border-black {{ request()->routeIs('dashboard') ? 'sidebar-link-active border-black' : 'text-black hover:bg-black/5' }}">
                <span>PAINEL</span>
            </a>

            <a href="{{ route('memories.index') }}"
               class="flex items-center gap-3 px-4 py-3 font-heading font-black text-lg no-underline transition-all border-4 border-transparent hover:border-black {{ request()->routeIs('memories.index') ? 'sidebar-link-active border-black' : 'text-black hover:bg-black/5' }}">
                <span>MEMÓRIAS</span>
            </a>

            <a href="{{ route('memories.create') }}"
               class="flex items-center gap-3 px-4 py-3 font-heading font-black text-lg no-underline transition-all border-4 border-transparent hover:border-black {{ request()->routeIs('memories.create') ? 'sidebar-link-active border-black' : 'text-black hover:bg-black/5' }}">
                <span>+ NOVA</span>
            </a>

            @if (auth()->user()?->is_admin)
            <div class="pt-4 mt-2 border-t-4 border-black/10">
                <span class="px-4 text-[10px] font-mono font-bold text-gray-500 uppercase tracking-[0.2em]">// pipeline</span>
            </div>

            <a href="{{ route('admin.captures') }}"
               class="flex items-center gap-3 px-4 py-3 font-heading font-black text-lg no-underline transition-all border-4 border-transparent hover:border-black {{ request()->routeIs('admin.captures') ? 'sidebar-link-active border-black' : 'text-black hover:bg-black/5' }}">
                <span>CAPTURAS</span>
            </a>

            <a href="{{ route('admin.skill-groups') }}"
               class="flex items-center gap-3 px-4 py-3 font-heading font-black text-lg no-underline transition-all border-4 border-transparent hover:border-black {{ request()->routeIs('admin.skill-groups') ? 'sidebar-link-active border-black' : 'text-black hover:bg-black/5' }}">
                <span>GRUPOS DE SKILLS</span>
            </a>

            <a href="{{ route('admin.skills') }}"
               class="flex items-center gap-3 px-4 py-3 font-heading font-black text-lg no-underline transition-all border-4 border-transparent hover:border-black {{ request()->routeIs('admin.skills') ? 'sidebar-link-active border-black' : 'text-black hover:bg-black/5' }}">
                <span>SKILLS</span>
            </a>

            <a href="{{ route('admin.tokens') }}"
               class="flex items-center gap-3 px-4 py-3 font-heading font-black text-lg no-underline transition-all border-4 border-transparent hover:border-black {{ request()->routeIs('admin.tokens') ? 'sidebar-link-active border-black' : 'text-black hover:bg-black/5' }}">
                <span>TOKENS MCP</span>
            </a>

            <a href="{{ route('admin.harness') }}"
               class="flex items-center gap-3 px-4 py-3 font-heading font-black text-lg no-underline transition-all border-4 border-transparent hover:border-black {{ request()->routeIs('admin.harness') ? 'sidebar-link-active border-black' : 'text-black hover:bg-black/5' }}">
                <span>HARNESS</span>
            </a>

            <a href="{{ route('admin.settings') }}"
               class="flex items-center gap-3 px-4 py-3 font-heading font-black text-lg no-underline transition-all border-4 border-transparent hover:border-black {{ request()->routeIs('admin.settings') ? 'sidebar-link-active border-black' : 'text-black hover:bg-black/5' }}">
                <span>CONFIGURAÇÕES</span>
            </a>
            @endif
        </nav>

        <!-- Profile Area -->
        @auth
        <div class="p-6 border-t-4 border-black bg-black/5 flex items-center gap-4">
             <div class="profile-avatar">{{ Str::upper(Str::substr(auth()->user()->name, 0, 2)) }}</div>
             <div class="flex flex-col flex-1 min-w-0">
                 <span class="font-heading font-black text-base uppercase leading-none truncate">{{ auth()->user()->name }}</span>
                 <div class="mt-1">
                     <span class="badge-system-root">SYSTEM_ROOT</span>
                 </div>
             </div>
             <form method="POST" action="{{ route('logout') }}">
                 @csrf
                 <button type="submit" title="Sair" aria-label="Sair"
                         class="btn-neo bg-neo-magenta neo-border-sm shadow-neo px-3 py-2 font-heading text-xs hover:bg-neo-yellow transition-colors">
                     &#x23Fb;
                 </button>
             </form>
        </div>
        @endauth
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative z-10">
        <header class="h-80px bg-neo-white border-b-4 border-black flex items-center px-4 sm:px-6 lg:px-8 justify-between flex-shrink-0 gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" @click="sidebarOpen = true" aria-label="Abrir menu"
                        :aria-expanded="sidebarOpen" aria-controls="sidebar-nav"
                        class="lg:hidden flex-shrink-0 btn-neo bg-neo-yellow neo-border-sm shadow-neo-sm p-2 hover:bg-neo-magenta transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div class="min-w-0">
                    <h2 class="font-heading font-black text-xl sm:text-2xl lg:text-3xl m-0 leading-tight tracking-tight truncate">{{ $title ?? 'TERMINAL' }}</h2>
                    <p class="text-xs text-gray-500 italic m-0 font-mono truncate hidden sm:block">root@nando-dev:~/memories/{{ strtolower($title ?? 'dashboard') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-4 flex-shrink-0">
                <div class="flex gap-2">
                    <div class="w-3 h-3 bg-red-500 border-2 border-black rounded-full"></div>
                    <div class="w-3 h-3 bg-yellow-500 border-2 border-black rounded-full"></div>
                    <div class="w-3 h-3 bg-green-500 border-2 border-black rounded-full"></div>
                </div>
            </div>
        </header>

        <div class="caution-scroll-container flex-shrink-0">
            <div class="caution-scroll-text">
                VALIDATED ARCHITECT /// PREVENT REGRESSIONS /// DOCUMENT EVERYTHING /// OPTIMIZE PATHS /// SYSTEM_ROOT ACCESS GRANTED /// VALIDATED ARCHITECT ///
            </div>
        </div>

        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-neo-bg relative">
            {{ $slot }}
        </main>

        <footer class="bg-black text-white p-2 text-center flex-shrink-0">
            <p class="font-mono text-[10px] m-0 uppercase tracking-[0.2em] opacity-70">
                Architect Sinistro v2.7 // Premium UI Effects // 2026-03-23
            </p>
        </footer>
    </div>

    {{-- highlight.js agora vem bundlado via Vite (resources/js/app.js) — sem CDN --}}
    <x-neo.toast />
    @livewireScripts
</body>
</html>
