<?php

declare(strict_types=1);

namespace App\Services\KnowledgeGraph;

use App\Enums\KnowledgeEdgeOrigin;
use App\Enums\KnowledgeEdgeStatus;
use App\Enums\KnowledgeRelationType;
use App\Models\Memory;
use App\Services\Curation\AnthropicCurationEngine;
use App\Services\Curation\CurationFailedException;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Extração governada de relações por IA: dadas duas memórias, pede ao motor de
 * curadoria que sugira a relação semântica entre elas (causa, resolve, suporta,
 * contradiz, etc.) com confiança e evidência. A aresta nasce sempre `proposed`
 * (status PROPOSED) — `KnowledgeGraphProjector::connect` força isso quando
 * `origin = AI_EXTRACTED`. Só validação humana promove a `validated`, e só
 * arestas `validated` aparecem em `memory_related`.
 */
final class RelationProposer
{
    private const RELATION_VALUES = [
        'causes', 'resolves', 'prevents', 'supports', 'contradicts',
        'supersedes', 'depends_on', 'derived_from', 'duplicates',
    ];

    public function __construct(
        private readonly AnthropicCurationEngine $engine,
        private readonly KnowledgeGraphProjector $projector,
    ) {}

    /**
     * Propõe uma relação entre source e target. Se `relationHint` for omitido,
     * o motor decide o tipo; caso contrário, valida a dica contra o enum.
     *
     * @return array{edge_id: string, relation: string, confidence: float, status: string, suggested_by_ai: bool}
     *
     * @throws InvalidArgumentException quando source === target, memória ausente
     *                                  ou relationHint inválido.
     * @throws CurationFailedException quando o motor não responde após retries.
     */
    public function propose(Memory $source, Memory $target, ?string $relationHint = null): array
    {
        if ($source->is($target)) {
            throw new InvalidArgumentException('Uma relação não pode ligar uma memória a ela mesma.');
        }

        if ($relationHint !== null && ! in_array($relationHint, self::RELATION_VALUES, true)) {
            throw new InvalidArgumentException(
                'Tipo de relação inválido. Valores permitidos: '.implode(', ', self::RELATION_VALUES),
            );
        }

        $verification = $this->askEngine($source, $target, $relationHint);

        $relation = KnowledgeRelationType::from($verification['relation']);
        $confidence = (float) $verification['confidence'];

        $sourceNode = $this->projector->projectMemory($source);
        $targetNode = $this->projector->projectMemory($target);

        $edge = $this->projector->connect(
            source: $sourceNode,
            target: $targetNode,
            relation: $relation,
            status: KnowledgeEdgeStatus::PROPOSED,
            origin: KnowledgeEdgeOrigin::AI_EXTRACTED,
            confidence: $confidence,
            inputHash: hash('sha256', $source->id.'|'.$target->id.'|'.$relation->value),
            evidence: [
                'source_type' => 'memory',
                'memory_id' => $source->id,
                'excerpt' => $verification['evidence_excerpt'] ?? null,
                'confidence' => $confidence,
                'metadata' => [
                    'suggested_relation' => $relation->value,
                    'target_memory_id' => $target->id,
                    'origin' => 'ai_extracted',
                ],
            ],
            properties: [
                'rationale' => $verification['rationale'] ?? null,
                'ai_suggested' => true,
            ],
        );

        return [
            'edge_id' => $edge->id,
            'relation' => $relation->value,
            'confidence' => $confidence,
            'status' => $edge->status->value,
            'suggested_by_ai' => true,
        ];
    }

    /**
     * @param  array<string>|null  $relationHint
     * @return array{relation: string, confidence: float, rationale: string, evidence_excerpt: ?string}
     */
    private function askEngine(Memory $source, Memory $target, ?string $relationHint): array
    {
        $allowed = implode(', ', self::RELATION_VALUES);
        $hintClause = $relationHint !== null
            ? "Considere o tipo sugerido \"{$relationHint}\" como viável, mas valide com evidência."
            : 'Escolha o tipo de relação mais adequado com base no conteúdo das duas memórias.';

        $system = <<<'PROMPT'
Você é um analista de conhecimento técnico. Dadas duas memórias de desenvolvimento,
sua tarefa é identificar a relação semântica mais provável entre elas e devolver um
objeto JSON estrito com a forma:

{
  "relation": "<um dos valores do enum>",
  "confidence": <float entre 0 e 1>,
  "rationale": "<uma frase curta justificando a relação>",
  "evidence_excerpt": "<trecho da memória de origem que fundamenta a relação, ou null>"
}

Relation deve ser um destes valores: causes, resolves, prevents, supports, contradicts,
supersedes, depends_on, derived_from, duplicates.

Regras:
- confidence baixa (<=0.4) quando a relação é especulativa.
- evidence_excerpt curto (<=200 caracteres), extraído da descrição da memória de origem.
- Nunca produza texto fora do objeto JSON.
PROMPT;

        $user = <<<USER
Memória de origem (id: {$source->id}):
Título: {$source->title}
Tipo: {$source->type->value}
Descrição: {$source->description}

Memória de destino (id: {$target->id}):
Título: {$target->title}
Tipo: {$target->type->value}
Descrição: {$target->description}

{$hintClause}
Relation disponíveis: {$allowed}.
USER;

        try {
            $result = $this->engine->completeJson(
                $system,
                $user,
                $this->validator(...),
            );
        } catch (CurationFailedException $e) {
            Log::warning('RelationProposer: motor falhou', [
                'source' => $source->id,
                'target' => $target->id,
                'hint' => $relationHint,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return [
            'relation' => (string) $result['relation'],
            'confidence' => (float) $result['confidence'],
            'rationale' => (string) ($result['rationale'] ?? ''),
            'evidence_excerpt' => isset($result['evidence_excerpt']) ? (string) $result['evidence_excerpt'] : null,
        ];
    }

    /**
     * @return array{relation: string, confidence: float, rationale: string, evidence_excerpt: ?string}
     */
    private function validator(mixed $payload): array
    {
        if (! is_array($payload) || ! isset($payload['relation'])) {
            throw new \RuntimeException('Resposta do motor deve ser um objeto com a chave "relation".');
        }

        $relation = (string) $payload['relation'];
        if (! in_array($relation, self::RELATION_VALUES, true)) {
            throw new \RuntimeException("relation inválido: {$relation}");
        }

        $confidence = (float) ($payload['confidence'] ?? -1);
        if ($confidence < 0 || $confidence > 1) {
            throw new \RuntimeException('confidence deve estar entre 0 e 1');
        }

        return [
            'relation' => $relation,
            'confidence' => $confidence,
            'rationale' => (string) ($payload['rationale'] ?? ''),
            'evidence_excerpt' => isset($payload['evidence_excerpt']) ? (string) $payload['evidence_excerpt'] : null,
        ];
    }
}
