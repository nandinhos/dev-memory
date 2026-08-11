<?php

namespace App\Models;

use App\Enums\KnowledgeNodeKind;
use Database\Factories\KnowledgeNodeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeNode extends Model
{
    /** @use HasFactory<KnowledgeNodeFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'kind',
        'memory_id',
        'namespace',
        'canonical_key',
        'label',
        'properties',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'kind' => KnowledgeNodeKind::class,
            'properties' => 'array',
        ];
    }

    public function memory(): BelongsTo
    {
        return $this->belongsTo(Memory::class);
    }

    public function outgoingEdges(): HasMany
    {
        return $this->hasMany(KnowledgeEdge::class, 'source_node_id');
    }

    public function incomingEdges(): HasMany
    {
        return $this->hasMany(KnowledgeEdge::class, 'target_node_id');
    }
}
