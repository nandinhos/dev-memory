<?php

namespace App\Livewire;

use App\Enums\MemoryType;
use App\Models\Memory;
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
        $this->totalMemories = Memory::count();
        $this->totalErrors = Memory::where('type', MemoryType::ERROR)->count();
        $this->totalLessons = Memory::where('type', MemoryType::LESSON)->count();
        $this->totalBestPractices = Memory::where('type', MemoryType::BEST_PRACTICE)->count();
        $this->totalValidated = Memory::where('validation_status', 'validated')->count();
        $this->totalPending = Memory::where('validation_status', 'pending')->count();
        $this->totalGlobal = Memory::where('scope', 'global')->count();
        $this->totalProject = Memory::where('scope', 'project')->count();

        $this->recentMemories = Memory::orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'type' => $m->type->label(),
                'type_color' => $m->type->value,
                'created_at' => $m->created_at->format('d/m/Y'),
            ])
            ->toArray();

        $this->topStacks = Memory::selectRaw('stack, COUNT(*) as count')
            ->whereNotNull('stack')
            ->groupBy('stack')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->toArray();
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
