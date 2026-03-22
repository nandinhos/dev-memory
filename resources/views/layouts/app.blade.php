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
<body class="bg-neo-bg min-h-screen">

    {{-- Faixa superior --}}
    <div class="sep-stripe"></div>

    <header class="bg-neo-white neo-border shadow-neo p-4 animate-slide-in">
        <div class="container mx-auto px-4">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 no-underline">
                    <div class="w-12 h-12 bg-neo-yellow neo-border shadow-neo flex items-center justify-center">
                        <span class="font-heading text-2xl font-bold text-black">DM</span>
                    </div>
                    <div>
                        <h1 class="font-heading text-2xl tracking-wide m-0 leading-none">
                            <span class="text-neo-magenta">DEV</span>MEMORY
                        </h1>
                        <span class="text-[10px] font-mono text-gray-500 uppercase tracking-widest">Sistema de Memória Técnica</span>
                    </div>
                </a>
                <nav class="flex gap-2">
                    <a href="{{ route('dashboard') }}"
                       class="btn-neo bg-neo-white neo-border-sm shadow-neo-sm px-4 py-2 font-heading text-sm hover:bg-neo-yellow transition-colors">
                        Dashboard
                    </a>
                    <a href="{{ route('memories.index') }}"
                       class="btn-neo bg-neo-white neo-border-sm shadow-neo-sm px-4 py-2 font-heading text-sm hover:bg-neo-teal transition-colors">
                        Lista
                    </a>
                    <a href="{{ route('memories.create') }}"
                       class="btn-neo bg-neo-green neo-border-sm shadow-neo px-4 py-2 font-heading text-sm hover:bg-neo-yellow transition-colors font-bold">
                        + Nova
                    </a>
                </nav>
            </div>
        </div>
    </header>

    {{-- Faixa separadora animada abaixo do header --}}
    <div class="sep-validated"></div>

    <main class="container mx-auto px-4 pb-12 mt-8">
        {{ $slot }}
    </main>

    <div class="sep-stripe mt-8"></div>

    <footer class="bg-neo-white neo-border shadow-neo p-4 text-center">
        <p class="font-mono text-xs m-0 uppercase tracking-widest">
            <span class="text-neo-magenta font-bold">DEV</span><span class="font-bold">MEMORY</span>
            <span class="text-gray-400 mx-2">·</span>
            Sistema de Memória Técnica e Lições Aprendidas
        </p>
    </footer>

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
