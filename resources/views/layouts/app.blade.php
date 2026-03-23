<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>{{ $title ?? 'Dev Memory' }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sistema de Memória Técnica e Lições Aprendidas">
    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/base16/catppuccin-mocha.min.css">
    @livewireStyles
</head>
<body class="bg-neo-bg min-h-screen flex overflow-hidden">

    <!-- Sidebar -->
    <aside class="sidebar-bege w-72 flex-shrink-0 flex flex-col h-screen relative z-20">
        <div class="logo-block">
            <a href="{{ route('dashboard') }}" class="no-underline">
                <span class="logo-text"><span class="logo-dev">DEV</span><span class="logo-memory">-MEMORY</span></span>
            </a>
        </div>

        <nav class="flex-1 overflow-y-auto p-6 space-y-4">
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-4 py-3 font-heading font-black text-lg no-underline transition-all border-4 border-transparent hover:border-black {{ request()->routeIs('dashboard') ? 'sidebar-link-active border-black' : 'text-black hover:bg-black/5' }}">
                <span>DASHBOARD</span>
            </a>

            <a href="{{ route('memories.index') }}"
               class="flex items-center gap-3 px-4 py-3 font-heading font-black text-lg no-underline transition-all border-4 border-transparent hover:border-black {{ request()->routeIs('memories.index') ? 'sidebar-link-active border-black' : 'text-black hover:bg-black/5' }}">
                <span>MEMORIES_LIST</span>
            </a>

            <a href="{{ route('memories.create') }}"
               class="flex items-center gap-3 px-4 py-3 font-heading font-black text-lg no-underline transition-all border-4 border-transparent hover:border-black {{ request()->routeIs('memories.create') ? 'sidebar-link-active border-black' : 'text-black hover:bg-black/5' }}">
                <span>+ NEW_ENTRY</span>
            </a>
        </nav>

        <!-- Profile Area -->
        <div class="p-6 border-t-4 border-black bg-black/5 flex items-center gap-4">
             <div class="profile-avatar">ND</div>
             <div class="flex flex-col">
                 <span class="font-heading font-black text-base uppercase leading-none">NANDO DEV</span>
                 <div class="mt-1">
                     <span class="badge-system-root">SYSTEM_ROOT</span>
                 </div>
             </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative z-10">
        <header class="h-80px bg-neo-white border-b-4 border-black flex items-center px-8 justify-between flex-shrink-0">
            <div>
                <h2 class="font-heading font-black text-3xl m-0 leading-tight tracking-tight">{{ $title ?? 'TERMINAL' }}</h2>
                <p class="text-xs text-gray-500 italic m-0 font-mono">root@nando-dev:~/memories/{{ strtolower($title ?? 'dashboard') }}</p>
            </div>
            <div class="flex items-center gap-4">
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

        <main class="flex-1 overflow-y-auto p-8 bg-neo-bg relative">
            {{ $slot }}
        </main>

        <footer class="bg-black text-white p-2 text-center flex-shrink-0">
            <p class="font-mono text-[10px] m-0 uppercase tracking-[0.2em] opacity-70">
                Architect Sinistro v2.7 // Premium UI Effects // 2026-03-23
            </p>
        </footer>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof hljs !== 'undefined') {
                hljs.highlightAll();
            }
        });
        document.addEventListener('livewire:navigated', function() {
            if (typeof hljs !== 'undefined') {
                hljs.highlightAll();
            }
        });
    </script>
    <x-neo.toast />
    @livewireScripts
</body>
</html>
