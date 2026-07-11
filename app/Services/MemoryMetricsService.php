<?php

namespace App\Services;

use App\Models\Memory;

class MemoryMetricsService
{
    public function getRecurrenceReport(): array
    {
        return [
            'top_recurring' => $this->topRecurring(),
            'by_stack' => $this->byStack(),
            'by_severity' => $this->bySeverity(),
            'by_type' => $this->byType(),
            'by_source' => $this->bySource(),
            'timeline' => $this->timeline(),
            'pending_count' => $this->pendingCount(),
        ];
    }

    public function topRecurring(int $limit = 10): array
    {
        return Memory::where('type', 'error')
            ->where('recurrence_count', '>', 1)
            ->orderByDesc('recurrence_count')
            ->limit($limit)
            ->get(['id', 'title', 'recurrence_count', 'stack', 'severity'])
            ->toArray();
    }

    public function byStack(): array
    {
        return Memory::whereNotNull('stack')
            ->selectRaw('stack, COUNT(*) as count, SUM(recurrence_count) as total_recurrence')
            ->groupBy('stack')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }

    public function bySeverity(): array
    {
        return Memory::whereNotNull('severity')
            ->selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->get()
            ->toArray();
    }

    public function byType(): array
    {
        return Memory::selectRaw('type, COUNT(*) as count, SUM(recurrence_count) as total_recurrence')
            ->groupBy('type')
            ->get()
            ->toArray();
    }

    public function bySource(): array
    {
        return Memory::whereNotNull('source_system')
            ->selectRaw('source_system, COUNT(*) as count')
            ->groupBy('source_system')
            ->get()
            ->toArray();
    }

    public function timeline(int $days = 30): array
    {
        return Memory::selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(recurrence_count) as total_recurrence')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    public function pendingCount(): int
    {
        return Memory::where('validation_status', 'pending')->count();
    }

    public function deduplicationSuggestions(int $threshold = 80): array
    {
        // Find memories with similar titles (simple Levenshtein-based)
        $memories = Memory::where('validation_status', '!=', 'rejected')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get(['id', 'title', 'type', 'stack', 'recurrence_count']);

        $suggestions = [];
        $checked = [];

        foreach ($memories as $memory) {
            foreach ($memories as $other) {
                if ($memory->id === $other->id) {
                    continue;
                }

                $key = min($memory->id, $other->id).'-'.max($memory->id, $other->id);
                if (in_array($key, $checked)) {
                    continue;
                }
                $checked[] = $key;

                $similarity = $this->titleSimilarity($memory->title, $other->title);
                if ($similarity >= $threshold) {
                    $suggestions[] = [
                        'memory_a' => ['id' => $memory->id, 'title' => $memory->title],
                        'memory_b' => ['id' => $other->id, 'title' => $other->title],
                        'similarity' => $similarity,
                    ];
                }
            }
        }

        return $suggestions;
    }

    private function titleSimilarity(string $titleA, string $titleB): int
    {
        $a = strtolower(trim($titleA));
        $b = strtolower(trim($titleB));

        if ($a === $b) {
            return 100;
        }

        // Simple word-based similarity
        $wordsA = array_filter(explode(' ', preg_replace('/[^a-z0-9 ]/', '', $a)));
        $wordsB = array_filter(explode(' ', preg_replace('/[^a-z0-9 ]/', '', $b)));

        $intersection = array_intersect($wordsA, $wordsB);
        $union = array_unique(array_merge($wordsA, $wordsB));

        if (count($union) === 0) {
            return 0;
        }

        return (int) round((count($intersection) / count($union)) * 100);
    }
}
