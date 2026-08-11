<?php

namespace App\Models;

use Database\Factories\KnowledgeEdgeEvidenceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeEdgeEvidence extends Model
{
    /** @use HasFactory<KnowledgeEdgeEvidenceFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'knowledge_edge_id',
        'capture_id',
        'memory_id',
        'source_type',
        'source_uri',
        'excerpt',
        'evidence_hash',
        'confidence',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'float',
            'metadata' => 'array',
        ];
    }

    public function edge(): BelongsTo
    {
        return $this->belongsTo(KnowledgeEdge::class, 'knowledge_edge_id');
    }

    public function memory(): BelongsTo
    {
        return $this->belongsTo(Memory::class);
    }

    public function capture(): BelongsTo
    {
        return $this->belongsTo(Capture::class);
    }
}
