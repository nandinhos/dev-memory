<?php

namespace App\Services\Curation;

use App\Models\Capture;
use App\Models\Memory;

/**
 * Composite recurrence scoring (P5): replaces title-only Levenshtein.
 * Components follow the original proposal; "semantic_similarity" is a
 * deterministic TF-cosine until pgvector arrives with the hub (F1).
 * Weights renormalize over available components so non-error memories
 * are not penalized for lacking an error signature.
 */
class RecurrenceScorer
{
    public const WEIGHTS = [
        'text' => 0.35,
        'error' => 0.25,
        'root_cause' => 0.20,
        'technology' => 0.10,
        'solution' => 0.10,
    ];

    /**
     * Floors calibrated against the real duplicate pairs from the
     * 2026-03-22 double seed (near-identical text scores 0.9+) and
     * paraphrased same-issue drafts (~0.55-0.75 text). The text floor
     * is the primary gate; the total adds cross-component discrimination.
     */
    public const TEXT_FLOOR = 0.55;

    public const TOTAL_FLOOR = 0.50;

    private const STOPWORDS = [
        'the', 'and', 'for', 'not', 'with', 'que', 'com', 'para', 'uma', 'não',
        'nao', 'dos', 'das', 'por', 'ser', 'usar', 'quando', 'como', 'mais',
    ];

    public function findMatch(LessonDraft $draft, ?Capture $capture = null): ?RecurrenceMatch
    {
        $best = null;

        foreach (Memory::query()->get() as $memory) {
            $score = $this->score($draft, $memory);

            if ($score->total < self::TOTAL_FLOOR || $score->components['text'] < self::TEXT_FLOOR) {
                continue;
            }

            if ($best === null || $score->total > $best->score->total) {
                $best = new RecurrenceMatch(
                    memory: $memory,
                    score: $score,
                    independent: $this->isIndependent($memory, $capture),
                );
            }
        }

        return $best;
    }

    public function score(LessonDraft $draft, Memory $memory): RecurrenceScore
    {
        $draftText = implode(' ', [$draft->title, $draft->summary, $draft->problem, $draft->solution]);
        $memoryText = $memory->title.' '.$memory->description;

        $components = [
            'text' => $this->cosine($draftText, $memoryText),
            'error' => $this->nullableCosine(
                $this->errorSignature($draftText),
                $this->errorSignature($memoryText),
            ),
            'root_cause' => $this->nullableCosine(
                $draft->rootCause,
                $this->extractSection($memory->description, 'Causa raiz') ?? $memory->description,
            ),
            'technology' => $this->technologyOverlap($draft, $memory),
            'solution' => $this->nullableCosine(
                $draft->solution,
                $this->extractSection($memory->description, 'Solução') ?? $memory->description,
            ),
        ];

        $total = 0.0;
        $weightSum = 0.0;

        foreach (self::WEIGHTS as $key => $weight) {
            if ($components[$key] === null) {
                continue;
            }

            $total += $components[$key] * $weight;
            $weightSum += $weight;
        }

        return new RecurrenceScore(
            components: array_map(fn ($value) => $value ?? 0.0, $components),
            total: $weightSum > 0 ? round($total / $weightSum, 4) : 0.0,
        );
    }

    /**
     * An occurrence only counts as recurrence when independent: a capture
     * from the same project with the same commit (or the same day, when
     * no commit is known) is the same incident, not a new occurrence.
     */
    private function isIndependent(Memory $memory, ?Capture $capture): bool
    {
        if ($capture === null) {
            return true;
        }

        $query = Capture::query()
            ->where('memory_id', $memory->id)
            ->where('id', '!=', $capture->id)
            ->where('source_project', $capture->source_project);

        $commit = $capture->metadata['commit'] ?? null;

        $commit !== null
            ? $query->where('metadata->commit', $commit)
            : $query->whereDate('created_at', $capture->created_at?->toDateString() ?? now()->toDateString());

        return ! $query->exists();
    }

    private function technologyOverlap(LessonDraft $draft, Memory $memory): ?float
    {
        $draftTechs = array_map(
            fn (array $tech) => mb_strtolower($tech['name']),
            $draft->technologies,
        );

        $memoryTechs = array_filter(array_map(
            fn (string $name) => mb_strtolower(trim($name)),
            explode(',', (string) $memory->stack),
        ));

        if ($draftTechs === [] || $memoryTechs === []) {
            return null;
        }

        $intersection = count(array_intersect($draftTechs, $memoryTechs));
        $union = count(array_unique(array_merge($draftTechs, $memoryTechs)));

        return $union > 0 ? $intersection / $union : null;
    }

    /**
     * Error signature: quoted messages, Exception/Error class names and
     * SQLSTATE codes — the parts of an error report that survive rewording.
     */
    private function errorSignature(string $text): ?string
    {
        preg_match_all(
            '/\'[^\']{5,80}\'|"[^"]{5,80}"|\b[A-Z][A-Za-z]+(?:Exception|Error)\b|SQLSTATE\[[^\]]+\]/u',
            $text,
            $matches,
        );

        return $matches[0] === [] ? null : implode(' ', $matches[0]);
    }

    private function extractSection(string $description, string $heading): ?string
    {
        if (! preg_match('/##\s*'.preg_quote($heading, '/').'\s*\n(.*?)(?=\n##\s|\z)/su', $description, $match)) {
            return null;
        }

        return trim($match[1]) ?: null;
    }

    private function nullableCosine(?string $a, ?string $b): ?float
    {
        if ($a === null || $b === null || trim($a) === '' || trim($b) === '') {
            return null;
        }

        return $this->cosine($a, $b);
    }

    private function cosine(string $a, string $b): float
    {
        $vectorA = $this->termFrequencies($a);
        $vectorB = $this->termFrequencies($b);

        if ($vectorA === [] || $vectorB === []) {
            return 0.0;
        }

        $dot = 0.0;

        foreach ($vectorA as $term => $frequency) {
            $dot += $frequency * ($vectorB[$term] ?? 0);
        }

        $normA = sqrt(array_sum(array_map(fn ($f) => $f * $f, $vectorA)));
        $normB = sqrt(array_sum(array_map(fn ($f) => $f * $f, $vectorB)));

        return $normA > 0 && $normB > 0 ? round($dot / ($normA * $normB), 4) : 0.0;
    }

    private function termFrequencies(string $text): array
    {
        $tokens = preg_split('/[^a-z0-9à-ÿ]+/iu', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY);

        $frequencies = [];

        foreach ($tokens as $token) {
            if (mb_strlen($token) < 3 || in_array($token, self::STOPWORDS, true)) {
                continue;
            }

            $frequencies[$token] = ($frequencies[$token] ?? 0) + 1;
        }

        return $frequencies;
    }
}

class RecurrenceScore
{
    public function __construct(
        public array $components,
        public float $total,
    ) {}

    public function toArray(): array
    {
        return ['components' => $this->components, 'total' => $this->total];
    }
}

class RecurrenceMatch
{
    public function __construct(
        public Memory $memory,
        public RecurrenceScore $score,
        public bool $independent,
    ) {}
}
