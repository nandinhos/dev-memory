<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>{{ $title ?? 'Dev Memory' }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sistema de Memória Técnica e Lições Aprendidas">
    @vite(['resources/css/app.css'])
    @livewireStyles
</head>
<body class="bg-neo-bg min-h-screen flex items-center justify-center p-6">
    <main class="w-full max-w-md">
        {{ $slot }}
    </main>
    <x-neo.toast />
    @livewireScripts
</body>
</html>
