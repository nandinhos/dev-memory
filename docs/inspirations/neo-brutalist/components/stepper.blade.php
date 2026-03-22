@props(['steps' => [], 'current' => 0])

<div class="flex items-start gap-0 overflow-x-auto" role="list">
    @foreach($steps as $index => $step)
        @php
            $isCompleted = $index < $current;
            $isActive    = $index === $current;
            $isPending   = $index > $current;
        @endphp

        <div class="flex flex-col items-center" role="listitem">
            <div class="flex items-center">
                {{-- Circle --}}
                <div
                    class="
                        flex items-center justify-center w-10 h-10 font-heading font-bold text-sm border-4 border-black
                        {{ $isCompleted ? 'bg-neo-green shadow-[4px_4px_0_#000]' : '' }}
                        {{ $isActive    ? 'bg-neo-yellow shadow-[4px_4px_0_#000]' : '' }}
                        {{ $isPending   ? 'bg-white' : '' }}
                    "
                    aria-label="{{ $step }}{{ $isCompleted ? ' (concluído)' : ($isActive ? ' (atual)' : ' (pendente)') }}"
                >
                    @if($isCompleted)
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    @else
                        {{ $index + 1 }}
                    @endif
                </div>

                {{-- Connector --}}
                @if(!$loop->last)
                    <div class="w-10 h-1 {{ $isCompleted ? 'bg-neo-green border-y-2 border-black' : 'bg-black' }} flex-shrink-0"></div>
                @endif
            </div>

            {{-- Label --}}
            <span class="mt-2 text-xs font-body font-bold uppercase tracking-wide text-center max-w-[5rem] leading-tight {{ $isActive ? 'text-black' : 'text-gray-500' }}">
                {{ $step }}
            </span>
        </div>
    @endforeach
</div>
