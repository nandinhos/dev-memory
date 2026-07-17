@props([
    'text' => '',
])

@php
use App\Helpers\CodeBlockHelper;

$content = $text ?? $slot ?? '';
$blocks = preg_split('/(```[\s\S]*?```)/', (string) $content, -1, PREG_SPLIT_DELIM_CAPTURE);

// Formatação inline SEGURA: escapa primeiro, depois aplica **negrito** e `código`.
$inline = function (string $s): string {
    $s = e($s);
    $s = preg_replace('/\*\*(.+?)\*\*/', '<strong class="font-bold text-black">$1</strong>', $s);
    $s = preg_replace('/`([^`]+)`/', '<code class="px-1.5 py-0.5 bg-black/[0.07] border border-black/10 text-[0.85em]">$1</code>', $s);

    return $s;
};
@endphp

<div class="prose-content font-mono text-sm leading-relaxed">
    @foreach($blocks as $index => $block)
        @if($index % 2 === 0)
            @php
            $blockText = trim($block);
            $lines = $blockText !== '' ? explode("\n", $blockText) : [];

            // Agrupa as linhas em elementos: título (#) / lista (- *) / parágrafo.
            $elements = [];
            $bullets = [];
            $flushBullets = function () use (&$elements, &$bullets) {
                if ($bullets !== []) {
                    $elements[] = ['t' => 'ul', 'items' => $bullets];
                    $bullets = [];
                }
            };
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '') {
                    continue;
                }
                if (preg_match('/^#{1,6}\s+(.*)$/', $trimmed, $m)) {
                    $flushBullets();
                    $elements[] = ['t' => 'h', 'text' => $m[1]];
                } elseif (preg_match('/^[-*]\s+(.*)$/', $trimmed, $m)) {
                    $bullets[] = $m[1];
                } else {
                    $flushBullets();
                    $elements[] = ['t' => 'p', 'text' => $trimmed];
                }
            }
            $flushBullets();
            @endphp

            @foreach($elements as $el)
                @if($el['t'] === 'h')
                    <h3 class="font-heading text-base uppercase tracking-tight text-black border-b-2 border-black/80 pb-1 mt-6 mb-2 first:mt-0">{!! $inline($el['text']) !!}</h3>
                @elseif($el['t'] === 'ul')
                    <ul class="space-y-1.5 mb-3 mt-1">
                        @foreach($el['items'] as $item)
                            <li class="flex gap-2 items-start">
                                <span class="text-neo-magenta font-black leading-snug select-none" aria-hidden="true">&#9656;</span>
                                <span class="flex-1">{!! $inline($item) !!}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mb-3">{!! $inline($el['text']) !!}</p>
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

            <div class="code-block rounded-lg overflow-hidden border-2 border-black shadow-neo-sm my-4">
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
