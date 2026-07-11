<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurationExecution extends Model
{
    use HasUuids;

    protected $fillable = [
        'capture_id',
        'pipeline_stage',
        'provider',
        'model',
        'prompt_version',
        'temperature',
        'input_hash',
        'output_hash',
        'attempts',
        'duration_ms',
        'usage',
        'status',
        'outcome',
        'error',
    ];

    protected $casts = [
        'usage' => 'array',
        'temperature' => 'float',
        'attempts' => 'integer',
        'duration_ms' => 'integer',
    ];

    public function capture(): BelongsTo
    {
        return $this->belongsTo(Capture::class);
    }
}
