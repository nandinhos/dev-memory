@props([
    'href' => '#',
    'label' => 'Download Kit',
])

<a href="{{ $href }}"
   class="fixed bottom-6 right-6 z-50 inline-flex items-center gap-2 px-5 py-3 bg-yellow-300 text-black font-heading text-sm uppercase neo-border shadow-neo hover:shadow-none hover:translate-x-[3px] hover:translate-y-[3px] transition-all duration-150"
   download>
    <i class="fa-solid fa-download"></i>
    <span>{{ $label }}</span>
</a>
