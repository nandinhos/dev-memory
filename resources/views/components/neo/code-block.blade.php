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
            const code = this.\$el.querySelector('code').innerText;
            navigator.clipboard.writeText(code).then(() => {
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2000);
            });
        }
    }"
    class="code-block border-2 border-black shadow-neo my-4 overflow-hidden"
>
    {{-- Titlebar macOS --}}
    <div class="flex items-center justify-between px-3 py-2 border-b-2 border-black"
         style="background-color: #2a2a3e;">

        {{-- Dots --}}
        <div class="flex items-center gap-1.5">
            <span class="w-[11px] h-[11px] rounded-full border border-black/20 flex-shrink-0"
                  style="background:#ff5f57;"></span>
            <span class="w-[11px] h-[11px] rounded-full border border-black/20 flex-shrink-0"
                  style="background:#febc2e;"></span>
            <span class="w-[11px] h-[11px] rounded-full border border-black/20 flex-shrink-0"
                  style="background:#28c840;"></span>
            @if($filename)
                <span class="ml-2 font-mono text-[11px]" style="color:#888;">{{ $filename }}</span>
            @endif
        </div>

        {{-- Botão copiar --}}
        <button
            @click="copyCode()"
            class="flex items-center gap-1 border border-black/40 px-2 py-0.5 font-mono text-[10px] transition-colors duration-100"
            :class="copied
                ? 'bg-neo-teal text-black border-neo-teal'
                : 'bg-white/10 text-gray-400 hover:bg-white/20 hover:text-white'"
            style="font-family: var(--font-mono);"
            aria-label="Copiar código"
        >
            <span x-show="!copied" aria-hidden="true">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
            </span>
            <span x-show="copied" aria-hidden="true">✓</span>
            <span x-text="copied ? 'Copiado' : 'Copiar'"></span>
        </button>
    </div>

    {{-- Corpo do código --}}
    <div class="overflow-x-auto" style="background-color: #1e1e2e;">
        <pre class="{{ $langClass }} !m-0 !rounded-none !border-0"
             style="background:transparent !important; padding: 1rem 1.25rem;"><code class="{{ $langClass }} code-with-lines">{!! $numbered !!}</code></pre>
    </div>
</div>
