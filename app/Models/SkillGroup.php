<?php

namespace App\Models;

use App\Enums\SkillGroupStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SkillGroup extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'purpose',
        'rationale',
        'cohesion',
        'status',
    ];

    protected $casts = [
        'cohesion' => 'float',
        'status' => SkillGroupStatus::class,
    ];

    public function memories(): BelongsToMany
    {
        return $this->belongsToMany(Memory::class);
    }
}
