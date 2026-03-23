@props([
    'language' => 'plaintext',
    'filename' => null,
])

@php
$langClass = match($language) {
    'php'                    => 'language-php',
    'js', 'javascript'       => 'language-javascript',
    'ts', 'typescript'       => 'language-typescript',
    'css'                    => 'language-css',
    'html'                   => 'language-html',
    'blade'                  => 'language-html',
    'json'                   => 'language-json',
    'bash', 'sh', 'shell'    => 'language-bash',
    'sql'                    => 'language-sql',
    'yaml', 'yml'            => 'language-yaml',
    'jsx', 'tsx'             => 'language-javascript',
    'md', 'markdown'         => 'language-markdown',
    default                  => 'language-plaintext',
};

// Adicionar números de linha: envolver cada linha em span
$rawCode = trim((string) $slot);
$lines = explode("\n", $rawCode);
$numbered = implode("\n", array_map(
    fn($i, $line) => '<span class="code-ln">' . ($i + 1) . '</span>' . htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
    array_keys($lines),
    $lines
));
@endphp

<div
    x-data="{
        copied: false,
        copyCode() {
            const codeEl = this.\$el.querySelector('code');
            const code = Array.from(codeEl.childNodes)
                .filter(n => !(n.nodeType === 1 && n.classList.contains('code-ln')))
                .map(n => n.textContent)
                .join('');
            navigator.clipboard.writeText(code).then(() => {
                this.copied = true;
                this.\$dispatch('show-toast', { message: 'CÓDIGO COPIADO!', type: 'sucesso' });
                setTimeout(() => { this.copied = false; }, 2000);
            }).catch(() => {});
        }
    }"
    class="code-block border-4 border-black shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] my-6 overflow-hidden relative"
>
    {{-- Titlebar macOS --}}
    <div class="flex items-center justify-between px-4 py-2 border-b-4 border-black"
         style="background-color: #2a2a3e;">

        {{-- Dots --}}
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full border-2 border-black/30 flex-shrink-0"
                  style="background:#ff5f57;"></span>
            <span class="w-3 h-3 rounded-full border-2 border-black/30 flex-shrink-0"
                  style="background:#febc2e;"></span>
            <span class="w-3 h-3 rounded-full border-2 border-black/30 flex-shrink-0"
                  style="background:#28c840;"></span>
            @if($filename)
                <span class="ml-2 font-mono text-[10px] uppercase font-bold" style="color:#64748B;">{{ $filename }}</span>
            @endif
        </div>

        {{-- Botão copiar: Neon Green Ghost --}}
        <button
            @click="copyCode()"
            class="group relative flex items-center gap-2 border-2 border-black px-3 py-1 font-mono text-[11px] font-bold uppercase transition-all duration-100"
            :class="copied
                ? 'bg-[#39FF14] text-black border-[#39FF14] animate-flash'
                : 'bg-transparent text-[#39FF14] border-[#39FF14] hover:bg-[#39FF14]/10'"
            aria-label="Copiar código"
        >
            <span x-show="!copied" class="flex items-center gap-2">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                COPIAR
            </span>
            <span x-show="copied">COPIADO!</span>
        </button>
    </div>

    {{-- Corpo do código: Catppuccin Mocha --}}
    <div class="relative overflow-hidden" style="background-color: #1e1e2e;">
        {{-- CRT Overlay integrada com opacidade 0.05 --}}
        <div class="crt-overlay opacity-5"></div>

        <div class="overflow-x-auto relative z-20">
            <pre class="{{ $langClass }} !m-0 !rounded-none !border-0"
                 style="background:transparent !important; padding: 1.5rem 1.25rem;"><code class="{{ $langClass }}">{!! $numbered !!}</code></pre>
        </div>
    </div>
</div>
