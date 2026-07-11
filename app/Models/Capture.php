<?php

namespace App\Models;

use App\Enums\CaptureStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Capture extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'source_system',
        'trigger_event',
        'source_project',
        'raw_content',
        'sanitized_content',
        'metadata',
        'idempotency_key',
        'status',
        'memory_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'status' => CaptureStatus::class,
    ];

    public function memory(): BelongsTo
    {
        return $this->belongsTo(Memory::class);
    }
}
