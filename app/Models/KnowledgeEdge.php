<?php

namespace App\Models;

use App\Enums\KnowledgeEdgeOrigin;
use App\Enums\KnowledgeEdgeStatus;
use App\Enums\KnowledgeRelationType;
use Database\Factories\KnowledgeEdgeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeEdge extends Model
{
    /** @use HasFactory<KnowledgeEdgeFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'source_node_id',
        'target_node_id',
        'relation_type',
        'status',
        'confidence',
        'origin',
        'extractor',
        'prompt_version',
        'input_hash',
        'properties',
    ];

    protected function casts(): array
    {
        return [
            'relation_type' => KnowledgeRelationType::class,
            'status' => KnowledgeEdgeStatus::class,
            'origin' => KnowledgeEdgeOrigin::class,
            'confidence' => 'float',
            'properties' => 'array',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(KnowledgeNode::class, 'source_node_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(KnowledgeNode::class, 'target_node_id');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(KnowledgeEdgeEvidence::class);
    }
}
