<?php

namespace App\Livewire;

use App\Enums\MemoryScope;
use App\Enums\MemoryType;
use App\Enums\ValidationStatus;
use App\Models\Memory;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
    public int $totalMemories = 0;

    public int $totalErrors = 0;

    public int $totalLessons = 0;

    public int $totalBestPractices = 0;

    public int $totalValidated = 0;

    public int $totalPending = 0;

    public int $totalGlobal = 0;

    public int $totalProject = 0;

    public array $recentMemories = [];

    public array $topStacks = [];

    public array $monthlyStats = [];

    public function mount(): void
    {
        $this->loadStats();
    }

    private function loadStats(): void
    {
        $stats = Cache::remember('dashboard_stats', 60, function () {
            return [
                'totalMemories'      => Memory::count(),
                'totalErrors'        => Memory::where('type', MemoryType::ERROR)->count(),
                'totalLessons'       => Memory::where('type', MemoryType::LESSON)->count(),
                'totalBestPractices' => Memory::where('type', MemoryType::BEST_PRACTICE)->count(),
                'totalValidated'     => Memory::where('validation_status', ValidationStatus::VALIDATED)->count(),
                'totalPending'       => Memory::where('validation_status', ValidationStatus::PENDING)->count(),
                'totalGlobal'        => Memory::where('scope', MemoryScope::GLOBAL)->count(),
                'totalProject'       => Memory::where('scope', MemoryScope::PROJECT)->count(),
                'recentMemories'     => Memory::orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(fn ($m) => [
                        'id'         => $m->id,
                        'title'      => $m->title,
                        'type'       => $m->type->label(),
                        'type_color' => $m->type->value,
                        'created_at' => $m->created_at->format('d/m/Y'),
                    ])
                    ->toArray(),
                'topStacks' => Memory::selectRaw('stack, COUNT(*) as count')
                    ->whereNotNull('stack')
                    ->groupBy('stack')
                    ->orderByDesc('count')
                    ->limit(5)
                    ->get()
                    ->toArray(),
            ];
        });

        $this->totalMemories      = $stats['totalMemories'];
        $this->totalErrors        = $stats['totalErrors'];
        $this->totalLessons       = $stats['totalLessons'];
        $this->totalBestPractices = $stats['totalBestPractices'];
        $this->totalValidated     = $stats['totalValidated'];
        $this->totalPending       = $stats['totalPending'];
        $this->totalGlobal        = $stats['totalGlobal'];
        $this->totalProject       = $stats['totalProject'];
        $this->recentMemories     = $stats['recentMemories'];
        $this->topStacks          = $stats['topStacks'];
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
