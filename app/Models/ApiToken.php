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
        'project_id',
        'is_global',
        'name',
        'token_hash',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_global' => 'boolean',
    ];

    protected $hidden = [
        'token_hash',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Issue a new token for a user, returning the model and the one-time
     * plaintext (only the SHA-256 hash is persisted).
     *
     * @return array{0: self, 1: string}
     */
    public static function issue(User $user, string $name, ?\DateTimeInterface $expiresAt = null, ?Project $project = null, bool $global = false): array
    {
        $plain = Str::random(48);

        if ($global && ! $user->is_admin) {
            throw new \InvalidArgumentException('Somente administradores podem emitir tokens globais.');
        }

        if (! $global && $project === null) {
            $slug = Str::slug($name) ?: 'default';
            $project = Project::firstOrCreate(
                ['user_id' => $user->id, 'slug' => $slug],
                ['name' => $name],
            );
        }

        if ($project !== null && $project->user_id !== $user->id && ! $user->is_admin) {
            throw new \InvalidArgumentException('O projeto precisa pertencer ao usuário que emite o token.');
        }

        $token = static::create([
            'user_id' => $user->id,
            'project_id' => $project?->id,
            'is_global' => $global,
            'name' => $name,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => $expiresAt,
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

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
