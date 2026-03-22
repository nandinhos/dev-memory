@props([
    'memoria' => null,
])

@php
$typeColors = [
    'error' => 'bg-neo-magenta',
    'lesson' => 'bg-neo-yellow',
    'best_practice' => 'bg-neo-green',
];

$scopeColors = [
    'project' => 'bg-neo-teal',
    'global' => 'bg-neo-purple',
];

$statusColors = [
    'pending' => 'bg-neo-salmon',
    'validated' => 'bg-neo-green',
    'rejected' => 'bg-gray-400',
];

$typeBg = $typeColors[$memoria->type->value] ?? 'bg-neo-teal';
$scopeBg = $scopeColors[$memoria->scope->value] ?? 'bg-neo-teal';
$statusBg = $statusColors[$memoria->validation_status->value] ?? 'bg-gray-400';
@endphp

<a href="{{ route('memories.show', $memoria) }}" 
   class="block card-neo bg-neo-white neo-border shadow-neo p-5 hover:shadow-neo-lg transition-all duration-150 group relative overflow-hidden">
    
    <div class="flex items-start justify-between gap-4 mb-3">
        <h3 class="font-heading text-lg leading-tight group-hover:text-neo-magenta transition-colors flex-1">
            {{ $memoria->title }}
        </h3>
        
        <div class="flex flex-col items-end gap-1 shrink-0">
            <span class="inline-block {{ $statusBg }} border-2 border-black px-2 py-0.5 text-[10px] font-bold font-heading">
                {{ $memoria->validation_status->label() }}
            </span>
        </div>
    </div>
    
    <p class="font-mono text-sm text-gray-700 mb-4 line-clamp-2">
        {{ Str::limit(strip_tags($memoria->description), 180) }}
    </p>
    
    <div class="relative">
        <div class="absolute -top-8 -right-2 flex flex-col items-center gap-1">
            @if($memoria->stack)
                <span class="bg-black text-white px-2 py-1 text-[10px] font-bold font-heading rotate-3 shadow-neo-sm">
                    {{ Str::limit($memoria->stack, 12) }}
                </span>
            @endif
            <span class="bg-neo-magenta border-2 border-black px-2 py-1 text-[10px] font-bold font-heading -rotate-2 shadow-neo-sm">
                {{ $memoria->recurrence_count }}x
            </span>
        </div>
        
        <div class="flex flex-wrap gap-1">
            <span class="inline-block {{ $typeBg }} border-2 border-black px-2 py-0.5 text-[10px] font-bold font-heading">
                {{ $memoria->type->label() }}
            </span>
            <span class="inline-block {{ $scopeBg }} border-2 border-black px-2 py-0.5 text-[10px] font-bold font-heading">
                {{ $memoria->scope->label() }}
            </span>
        </div>
    </div>
    
    <div class="mt-3 pt-3 border-t-2 border-black/20 flex items-center justify-between">
        <span class="text-[10px] font-mono text-gray-500">
            {{ $memoria->created_at->format('d/m/Y') }}
        </span>
        <span class="text-[10px] font-mono text-gray-400 group-hover:text-neo-magenta transition-colors">
            Ver →
        </span>
    </div>
</a>
