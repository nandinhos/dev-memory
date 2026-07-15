<?php

namespace App\Services;

use App\Enums\MemoryType;
use App\Enums\SkillStatus;
use App\Enums\ValidationStatus;
use App\Models\Memory;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Preventive consultation: given a context (stack + description), returns
 * the accumulated knowledge relevant BEFORE implementation — known risks,
 * approved patterns, related lessons, recurring problems and published
 * skills. Deterministic and fast (no LLM) so it fits the planning moment.
 */
class HubBriefingService
{
    public function briefing(?string $stack = null, ?string $description = null): array
    {
        $risks = $this->format(
            $this->validated($stack)->where('type', MemoryType::ERROR)
                ->orderByDesc('recurrence_count')->limit(8)->get()
        );

        $patterns = $this->format(
            $this->validated($stack)->where('type', MemoryType::BEST_PRACTICE)
                ->orderByDesc('recurrence_count')->limit(8)->get()
        );

        $lessons = $this->format(
            $this->validated($stack)->where('type', MemoryType::LESSON)
                ->orderByDesc('recurrence_count')->limit(8)->get()
        );

        $related = $this->format($this->matchDescription($description));

        return [
            'context' => ['stack' => $stack, 'description' => $description],
            'known_risks' => $risks,
            'approved_patterns' => $patterns,
            'relevant_lessons' => $lessons,
            'related_to_description' => $related,
            'skills' => $this->skills($stack),
            'summary' => sprintf(
                '%d risco(s) conhecido(s), %d padrão(ões) aprovado(s), %d lição(ões) relevante(s)%s.',
                count($risks),
                count($patterns),
                count($lessons),
                $stack ? " para {$stack}" : '',
            ),
        ];
    }

    private function validated(?string $stack): Builder
    {
        return Memory::query()
            ->where('validation_status', ValidationStatus::VALIDATED)
            ->when($stack, fn (Builder $q, string $s) => $q->where('stack', 'like', "%{$s}%"));
    }

    private function matchDescription(?string $description): Collection
    {
        if ($description === null || trim($description) === '') {
            return collect();
        }

        $terms = collect(preg_split('/\s+/', $description))
            ->map(fn ($t) => trim($t))
            ->filter(fn ($t) => mb_strlen($t) >= 4)
            ->take(6);

        if ($terms->isEmpty()) {
            return collect();
        }

        return Memory::query()
            ->where('validation_status', ValidationStatus::VALIDATED)
            ->where(function (Builder $q) use ($terms) {
                foreach ($terms as $term) {
                    $q->orWhere('title', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                }
            })
            ->orderByDesc('recurrence_count')
            ->limit(8)
            ->get();
    }

    private function skills(?string $stack): array
    {
        return Skill::where('status', SkillStatus::PUBLISHED)
            ->get()
            ->filter(function (Skill $skill) use ($stack) {
                if ($stack === null) {
                    return true;
                }

                foreach ($skill->manifest['activation']['technologies'] ?? [] as $tech) {
                    if (stripos($tech, $stack) !== false || stripos($stack, $tech) !== false) {
                        return true;
                    }
                }

                return false;
            })
            ->map(fn (Skill $s) => ['slug' => $s->slug, 'name' => $s->name, 'version' => $s->version])
            ->values()
            ->toArray();
    }

    private function format(Collection $memories): array
    {
        return $memories->map(fn (Memory $m) => [
            'id' => $m->id,
            'title' => $m->title,
            'stack' => $m->stack,
            'recurrence' => $m->recurrence_count,
            'reference' => $m->official_reference,
        ])->values()->toArray();
    }
}
