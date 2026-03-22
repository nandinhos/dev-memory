@props([
    'text' => '',
])

@php
use App\Helpers\CodeBlockHelper;

$content = $text ?? $slot ?? '';
$blocks = preg_split('/(```[\s\S]*?```)/', (string) $content, -1, PREG_SPLIT_DELIM_CAPTURE);
@endphp

<div class="prose-content font-mono text-sm leading-relaxed space-y-4">
    @foreach($blocks as $index => $block)
        @if($index % 2 === 0)
            @php
            $blockText = trim($block);
            $lines = $blockText !== '' ? explode("\n", $blockText) : [];
            @endphp
            
            @foreach($lines as $line)
                @if(trim($line) !== '')
                    <p class="mb-2 whitespace-pre-wrap">{{ $line }}</p>
                @endif
            @endforeach
        @else
            @php
            preg_match('/^```(\w*)/', $block, $matches);
            $lang = !empty($matches[1]) ? $matches[1] : 'plaintext';
            $code = preg_replace('/^```\w*\n?/', '', $block);
            $code = preg_replace('/```$/', '', $code);
            $hljsLang = CodeBlockHelper::getHljsLang($lang);
            @endphp
            
            <div class="code-block rounded-lg overflow-hidden border-2 border-black shadow-neo-sm">
                <div class="bg-zinc-800 px-4 py-2 flex items-center justify-between border-b-2 border-black">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-[#ff5f56]"></span>
                        <span class="w-3 h-3 rounded-full bg-[#ffbd2e]"></span>
                        <span class="w-3 h-3 rounded-full bg-[#27c93f]"></span>
                    </div>
                    @if($lang !== 'plaintext')
                        <span class="text-zinc-400 text-xs font-mono uppercase font-bold">{{ $lang }}</span>
                    @endif
                </div>
                <div class="bg-[#2B2B2B] px-4 py-3 overflow-x-auto">
                    <pre class="language-{{ $hljsLang }}"><code class="language-{{ $hljsLang }}">{{ trim($code) }}</code></pre>
                </div>
            </div>
        @endif
    @endforeach
</div>
