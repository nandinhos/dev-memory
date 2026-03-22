<?php

namespace App\Models;

use App\Enums\MemoryScope;
use App\Enums\MemoryType;
use App\Enums\ValidationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Memory extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'type',
        'stack',
        'scope',
        'validation_status',
        'official_reference',
        'recurrence_count',
    ];

    protected $casts = [
        'type' => MemoryType::class,
        'scope' => MemoryScope::class,
        'validation_status' => ValidationStatus::class,
        'recurrence_count' => 'integer',
    ];

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type)
        );
        $query->when($filters['stack'] ?? null, fn ($q, $stack) => $q->where('stack', 'like', "%{$stack}%")
        );
        $query->when($filters['scope'] ?? null, fn ($q, $scope) => $q->where('scope', $scope)
        );
        $query->when($filters['search'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
            ->orWhere('description', 'like', "%{$search}%")
        )
        );
    }

    public function scopeErrors($query)
    {
        return $query->where('type', MemoryType::ERROR);
    }

    public function scopeLessons($query)
    {
        return $query->where('type', MemoryType::LESSON);
    }

    public function scopeBestPractices($query)
    {
        return $query->where('type', MemoryType::BEST_PRACTICE);
    }

    public function scopeGlobal($query)
    {
        return $query->where('scope', MemoryScope::GLOBAL);
    }

    public function scopeProject($query)
    {
        return $query->where('scope', MemoryScope::PROJECT);
    }

    public function scopeValidated($query)
    {
        return $query->where('validation_status', ValidationStatus::VALIDATED);
    }
}
