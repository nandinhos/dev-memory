<?php

namespace App\Models;

use App\Enums\HarnessType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HarnessProfile extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'harness',
        'name',
        'version',
        'description',
        'files',
    ];

    protected $casts = [
        'harness' => HarnessType::class,
        'files' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
