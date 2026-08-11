<?php

namespace App\Services\KnowledgeGraph;

use App\Enums\KnowledgeEdgeOrigin;
use App\Enums\KnowledgeEdgeStatus;
use App\Enums\KnowledgeNodeKind;
use App\Enums\KnowledgeRelationType;
use App\Models\KnowledgeEdge;
use App\Models\KnowledgeNode;
use App\Models\Memory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class KnowledgeGraphProjector
{
    public function projectMemory(Memory $memory): KnowledgeNode
    {
        return DB::transaction(function () use ($memory) {
            $memoryNode = KnowledgeNode::updateOrCreate(
                ['namespace' => 'memory', 'canonical_key' => $memory->id],
                [
                    'kind' => KnowledgeNodeKind::MEMORY,
                    'memory_id' => $memory->id,
                    'label' => $memory->title,
                    'status' => 'active',
                    'properties' => [
                        'type' => $memory->type->value,
                        'scope' => $memory->scope->value,
                        'maturity' => $memory->maturity?->value,
                        'project_id' => $memory->project_id,
                    ],
                ],
            );

            $technologyNodeIds = [];

            foreach ($this->technologies($memory->stack) as $technology) {
                $normalized = $this->normalize($technology);
                $technologyNode = KnowledgeNode::updateOrCreate(
                    [
                        'namespace' => 'technology',
                        'canonical_key' => hash('sha256', $normalized),
                    ],
                    [
                        'kind' => KnowledgeNodeKind::TECHNOLOGY,
                        'label' => $technology,
                        'status' => 'active',
                        'properties' => ['normalized_name' => $normalized],
                    ],
                );

                $technologyNodeIds[] = $technologyNode->id;

                $this->connect(
                    source: $memoryNode,
                    target: $technologyNode,
                    relation: KnowledgeRelationType::APPLIES_TO,
                    status: KnowledgeEdgeStatus::VALIDATED,
                    origin: KnowledgeEdgeOrigin::DETERMINISTIC,
                    confidence: 1.0,
                    inputHash: hash('sha256', $memory->id.'|'.$normalized),
                    evidence: [
                        'source_type' => 'memory',
                        'memory_id' => $memory->id,
                        'excerpt' => "Stack: {$technology}",
                        'confidence' => 1.0,
                    ],
                );
            }

            $staleEdges = KnowledgeEdge::query()
                ->where('source_node_id', $memoryNode->id)
                ->where('relation_type', KnowledgeRelationType::APPLIES_TO->value)
                ->where('origin', KnowledgeEdgeOrigin::DETERMINISTIC->value)
                ->whereHas('target', fn ($query) => $query->where('kind', KnowledgeNodeKind::TECHNOLOGY->value));

            if ($technologyNodeIds === []) {
                $staleEdges->delete();
            } else {
                $staleEdges->whereNotIn('target_node_id', $technologyNodeIds)->delete();
            }

            return $memoryNode->fresh();
        });
    }

    public function connect(
        KnowledgeNode $source,
        KnowledgeNode $target,
        KnowledgeRelationType $relation,
        KnowledgeEdgeStatus $status,
        KnowledgeEdgeOrigin $origin,
        float $confidence,
        ?string $inputHash = null,
        ?array $evidence = null,
        array $properties = [],
    ): KnowledgeEdge {
        if ($source->is($target)) {
            throw new InvalidArgumentException('Uma aresta de conhecimento não pode conectar um nó a ele mesmo.');
        }

        if ($confidence < 0 || $confidence > 1) {
            throw new InvalidArgumentException('A confiança da aresta deve estar entre 0 e 1.');
        }

        if ($origin === KnowledgeEdgeOrigin::AI_EXTRACTED || ($relation->requiresHumanReview() && $origin !== KnowledgeEdgeOrigin::HUMAN)) {
            $status = KnowledgeEdgeStatus::PROPOSED;
        }

        $edge = KnowledgeEdge::firstOrNew([
            'source_node_id' => $source->id,
            'target_node_id' => $target->id,
            'relation_type' => $relation->value,
        ]);

        $wouldDowngradeValidatedEdge = $edge->exists
            && $edge->status === KnowledgeEdgeStatus::VALIDATED
            && $status === KnowledgeEdgeStatus::PROPOSED;

        if (! $wouldDowngradeValidatedEdge) {
            $edge->fill([
                'status' => $status,
                'confidence' => $confidence,
                'origin' => $origin,
                'extractor' => $origin === KnowledgeEdgeOrigin::DETERMINISTIC ? self::class : null,
                'input_hash' => $inputHash,
                'properties' => $properties,
            ])->save();
        }

        if ($evidence !== null) {
            $evidenceHash = hash('sha256', implode('|', [
                $evidence['source_type'] ?? '',
                $evidence['capture_id'] ?? '',
                $evidence['memory_id'] ?? '',
                $evidence['source_uri'] ?? '',
                $evidence['excerpt'] ?? '',
            ]));

            $edge->evidence()->updateOrCreate(
                ['evidence_hash' => $evidenceHash],
                [
                    'capture_id' => $evidence['capture_id'] ?? null,
                    'memory_id' => $evidence['memory_id'] ?? null,
                    'source_type' => $evidence['source_type'] ?? 'unknown',
                    'source_uri' => $evidence['source_uri'] ?? null,
                    'excerpt' => $evidence['excerpt'] ?? null,
                    'confidence' => $evidence['confidence'] ?? $confidence,
                    'metadata' => $evidence['metadata'] ?? [],
                ],
            );
        }

        return $edge->fresh('evidence');
    }

    /** @return array<int, string> */
    private function technologies(?string $stack): array
    {
        if ($stack === null || trim($stack) === '') {
            return [];
        }

        return collect(preg_split('/[,;|]/', $stack))
            ->map(fn (string $technology) => trim($technology))
            ->filter()
            ->unique(fn (string $technology) => $this->normalize($technology))
            ->values()
            ->all();
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $value)));
    }
}
