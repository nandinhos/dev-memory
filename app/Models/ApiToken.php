<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'name',
        'token_hash',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'token_hash',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Issue a new token for a user, returning the model and the one-time
     * plaintext (only the SHA-256 hash is persisted).
     *
     * @return array{0: self, 1: string}
     */
    public static function issue(User $user, string $name): array
    {
        $plain = Str::random(48);

        $token = static::create([
            'user_id' => $user->id,
            'name' => $name,
            'token_hash' => hash('sha256', $plain),
        ]);

        return [$token, $plain];
    }

    public static function findByPlaintext(?string $plain): ?self
    {
        if ($plain === null || $plain === '') {
            return null;
        }

        return static::firstWhere('token_hash', hash('sha256', $plain));
    }
}
