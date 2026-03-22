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
    <header class="bg-neo-white neo-border shadow-neo p-4 mb-8 animate-slide-in">
        <div class="container mx-auto px-4">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 no-underline">
                    <div class="w-12 h-12 bg-neo-teal neo-border-sm shadow-neo flex items-center justify-center">
                        <span class="font-heading text-2xl font-bold text-neo-black">DM</span>
                    </div>
                    <div>
                        <h1 class="font-heading text-2xl tracking-wide m-0">DEV MEMORY</h1>
                        <span class="text-xs font-mono text-gray-600">Sistema de Memória Técnica</span>
                    </div>
                </a>
                <nav class="flex gap-2">
                    <a href="{{ route('dashboard') }}" class="btn-neo bg-neo-white neo-border-sm shadow-neo-sm px-4 py-2 font-heading text-sm hover:bg-neo-yellow transition-colors">
                        Dashboard
                    </a>
                    <a href="{{ route('memories.index') }}" class="btn-neo bg-neo-white neo-border-sm shadow-neo-sm px-4 py-2 font-heading text-sm hover:bg-neo-yellow transition-colors">
                        Lista
                    </a>
                    <a href="{{ route('memories.create') }}" class="btn-neo bg-neo-teal neo-border-sm shadow-neo-sm px-4 py-2 font-heading text-sm hover:bg-neo-yellow transition-colors">
                        + Nova
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 pb-12">
        {{ $slot }}
    </main>

    <footer class="bg-neo-white neo-border shadow-neo p-4 mt-8 text-center">
        <p class="font-mono text-sm m-0">
            <span class="text-neo-teal">DEV</span>MEMORY 
            <span class="text-gray-400">·</span> 
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
