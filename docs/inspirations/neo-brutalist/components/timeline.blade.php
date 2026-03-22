@props([
    'eventos'     => [],
    'orientacao'  => 'vertical',
])

<div {{ $attributes->merge(['class' => 'relative']) }}>
    @if($orientacao === 'vertical')
        <div class="flex flex-col">
            @foreach($eventos as $i => $evento)
                @php
                    $status = $evento['status'] ?? 'pending';
                    $ultimo = $loop->last;
                @endphp
                <div class="flex gap-4 {{ !$ultimo ? 'pb-2' : '' }}">
                    {{-- Coluna da linha + bullet --}}
                    <div class="flex flex-col items-center flex-shrink-0">
                        {{-- Bullet --}}
                        @if($status === 'done')
                            <div class="w-5 h-5 bg-neo-green border-2 border-black flex items-center justify-center z-10 flex-shrink-0">
                                <svg class="w-3 h-3" viewBox="0 0 12 12" fill="none" stroke="black" stroke-width="2" stroke-linecap="round">
                                    <polyline points="2,6 5,9 10,3"/>
                                </svg>
                            </div>
                        @elseif($status === 'active')
                            <div class="w-5 h-5 bg-neo-yellow border-2 border-black animate-pulse z-10 flex-shrink-0"></div>
                        @else
                            <div class="w-5 h-5 bg-white border-2 border-black z-10 flex-shrink-0"></div>
                        @endif

                        {{-- Linha vertical --}}
                        @if(!$ultimo)
                            <div class="w-0.5 bg-black flex-1 mt-1" style="min-height: 1.5rem;"></div>
                        @endif
                    </div>

                    {{-- Card do evento --}}
                    <div class="flex-1 bg-white neo-border shadow-neo p-3 mb-4">
                        @if(!empty($evento['data']))
                            <p class="font-heading font-bold text-xs uppercase tracking-wider text-gray-500 mb-1">{{ $evento['data'] }}</p>
                        @endif
                        <p class="font-heading font-bold uppercase text-sm leading-tight">{{ $evento['titulo'] ?? '' }}</p>
                        @if(!empty($evento['descricao']))
                            <p class="font-body text-xs text-gray-600 mt-1 leading-snug">{{ $evento['descricao'] }}</p>
                        @endif
                        @if(!empty($evento['icone']))
                            <span class="inline-block mt-2 text-gray-500 font-body text-xs">{{ $evento['icone'] }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
