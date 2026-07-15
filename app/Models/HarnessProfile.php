<?php

namespace App\Models;

use App\Enums\HarnessType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class HarnessProfile extends Model
{
    use HasUuids;

    protected $fillable = [
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
}
