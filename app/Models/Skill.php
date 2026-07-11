<?php

namespace App\Models;

use App\Enums\SkillStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Skill extends Model
{
    use HasUuids;

    protected $fillable = [
        'skill_group_id',
        'slug',
        'name',
        'version',
        'manifest',
        'status',
    ];

    protected $casts = [
        'manifest' => 'array',
        'status' => SkillStatus::class,
    ];

    public function skillGroup(): BelongsTo
    {
        return $this->belongsTo(SkillGroup::class);
    }
}
