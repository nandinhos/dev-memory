@props([
    'language' => 'plaintext',
])

@php
$langClass = match($language) {
    'php' => 'language-php',
    'js', 'javascript' => 'language-javascript',
    'ts', 'typescript' => 'language-typescript',
    'css' => 'language-css',
    'html' => 'language-html',
    'blade' => 'language-html',
    'json' => 'language-json',
    'bash', 'sh', 'shell' => 'language-bash',
    'sql' => 'language-sql',
    'yaml', 'yml' => 'language-yaml',
    'jsx', 'tsx' => 'language-javascript',
    'md', 'markdown' => 'language-markdown',
    default => 'language-plaintext',
};
@endphp

<div class="code-block rounded-lg overflow-hidden border-2 border-black shadow-neo-sm my-4">
    <div class="bg-zinc-800 px-4 py-2 flex items-center justify-between border-b-2 border-black">
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-[#ff5f56]"></span>
            <span class="w-3 h-3 rounded-full bg-[#ffbd2e]"></span>
            <span class="w-3 h-3 rounded-full bg-[#27c93f]"></span>
        </div>
        @if($language !== 'plaintext')
            <span class="text-zinc-400 text-xs font-mono uppercase font-bold">{{ $language }}</span>
        @endif
    </div>
    <div class="bg-[#2B2B2B] px-4 py-3 overflow-x-auto">
        <pre class="{{ $langClass }}"><code class="{{ $langClass }}">{{ trim($slot) }}</code></pre>
    </div>
</div>
