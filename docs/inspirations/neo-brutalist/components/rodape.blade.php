@props(['texto' => '© 2025 Neo-Brutalist Design System'])

<footer class="bg-white neo-border shadow-neo p-4 mt-8 text-center">
    <p class="font-body text-sm font-bold uppercase tracking-widest">{{ $texto }}</p>
    @isset($slot)
        <div class="mt-2">{{ $slot }}</div>
    @endisset
</footer>
