<?php

namespace App\Services;

use App\Enums\MemoryType;
use App\Enums\SkillStatus;
use App\Enums\ValidationStatus;
use App\Models\Memory;
use App\Models\Skill;
use App\Services\KnowledgeGraph\KnowledgeGraphQueryService;
use App\Services\Search\MemorySearchService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Preventive consultation (Graph RAG): given a context (stack + description),
 * returns the accumulated knowledge relevant BEFORE implementation — known risks,
 * approved patterns, related lessons, recurring problems, knowledge graph relations,
 * and published skills. Deterministic and fast so it fits the planning moment.
 */
class HubBriefingService
{
    public function __construct(
        private ?MemorySearchService $searchService = null,
        private ?KnowledgeGraphQueryService $graphQueryService = null,
    ) {
        $this->searchService ??= app(MemorySearchService::class);
        $this->graphQueryService ??= app(KnowledgeGraphQueryService::class);
    }

    public function briefing(?string $stack = null, ?string $description = null, ?string $visibleProjectId = null): array
    {
        $risks = $this->format(
            $this->visible($this->validated($stack), $visibleProjectId)->where('type', MemoryType::ERROR)
                ->orderByDesc('recurrence_count')->limit(8)->get()
        );

        $patterns = $this->format(
            $this->visible($this->validated($stack), $visibleProjectId)->where('type', MemoryType::BEST_PRACTICE)
                ->orderByDesc('recurrence_count')->limit(8)->get()
        );

        $lessons = $this->format(
            $this->visible($this->validated($stack), $visibleProjectId)->where('type', MemoryType::LESSON)
                ->orderByDesc('recurrence_count')->limit(8)->get()
        );

        $related = $this->format($this->matchDescription($description, $visibleProjectId, $stack));

        $graphInsights = $this->extractGraphInsights([...$risks, ...$patterns, ...$lessons, ...$related]);

        return [
            'context' => ['stack' => $stack, 'description' => $description],
            'known_risks' => $risks,
            'approved_patterns' => $patterns,
            'relevant_lessons' => $lessons,
            'related_to_description' => $related,
            'graph_insights' => $graphInsights,
            'skills' => $visibleProjectId === null ? $this->skills($stack) : [],
            'summary' => sprintf(
                '%d risco(s) conhecido(s), %d padrão(ões) aprovado(s), %d lição(ões) relevante(s)%s%s.',
                count($risks),
                count($patterns),
                count($lessons),
                $stack ? " para {$stack}" : '',
                count($graphInsights) > 0 ? sprintf(', %d conexão(ões) no grafo', count($graphInsights)) : '',
            ),
        ];
    }

    private function validated(?string $stack): Builder
    {
        return Memory::query()
            ->where('validation_status', ValidationStatus::VALIDATED)
            ->when($stack, fn (Builder $q, string $s) => $q->where('stack', 'like', "%{$s}%"));
    }

    private function matchDescription(?string $description, ?string $visibleProjectId, ?string $stack = null): Collection
    {
        if ($description === null || trim($description) === '') {
            return collect();
        }

        $found = collect();

        // 1. Tenta busca híbrida semântica/lexical se o searchService estiver disponível
        if ($this->searchService !== null) {
            try {
                $filters = array_filter([
                    'project_id' => $visibleProjectId,
                    'stack' => $stack,
                ]);
                $searchResult = $this->searchService->search($description, $filters, limit: 8);
                $found = collect($searchResult['results'] ?? [])
                    ->map(fn ($item) => is_array($item) ? ($item['memory'] ?? null) : $item)
                    ->filter(fn ($m) => $m instanceof Memory && $m->validation_status === ValidationStatus::VALIDATED);
            } catch (\Throwable) {
                // Degradação graciosa para busca léxica direta
            }
        }

        // 2. Mescla com busca léxica por termos para garantir cobertura de palavras-chave exatas
        $terms = collect(preg_split('/\s+/', $description))
            ->map(fn ($t) => trim($t))
            ->filter(fn ($t) => mb_strlen($t) >= 4)
            ->take(6);

        if ($terms->isNotEmpty()) {
            $lexical = $this->visible(Memory::query()
                ->where('validation_status', ValidationStatus::VALIDATED)
                ->where(function (Builder $q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->orWhere('title', 'like', "%{$term}%")
                            ->orWhere('description', 'like', "%{$term}%");
                    }
                })
                ->orderByDesc('recurrence_count')
                ->limit(8), $visibleProjectId)
                ->get();

            $found = $found->concat($lexical)->unique('id')->take(8);
        }

        return $found;
    }

    private function extractGraphInsights(array $formattedMemories): array
    {
        if ($this->graphQueryService === null || empty($formattedMemories)) {
            return [];
        }

        $insights = [];
        $ids = collect($formattedMemories)->pluck('id')->unique()->take(5);

        foreach ($ids as $id) {
            $memory = Memory::find($id);
            if (! $memory) {
                continue;
            }

            try {
                $related = $this->graphQueryService->relatedTo($memory, depth: 2, limit: 3);
                foreach ($related as $rel) {
                    $targetMem = $rel['memory'] ?? null;
                    if (! $targetMem) {
                        continue;
                    }

                    foreach ($rel['path'] ?? [] as $segment) {
                        $insights[] = [
                            'source_id' => $memory->id,
                            'source_title' => $memory->title,
                            'relation' => $segment['relation'] ?? 'related_to',
                            'direction' => $segment['direction'] ?? 'outgoing',
                            'target_id' => $targetMem->id,
                            'target_title' => $targetMem->title,
                            'target_stack' => $targetMem->stack,
                            'confidence' => $segment['confidence'] ?? 1.0,
                        ];
                    }
                }
            } catch (\Throwable) {
                // Tolerante a falhas na consulta de grafos
            }
        }

        return collect($insights)->unique(fn ($item) => $item['source_id'].'-'.$item['target_id'].'-'.$item['relation'])->values()->all();
    }

    private function visible(Builder $query, ?string $projectId): Builder
    {
        if ($projectId === null) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($projectId) {
            $query->where('scope', 'global')
                ->orWhere(fn (Builder $query) => $query->where('scope', 'project')->where('project_id', $projectId));
        });
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
