<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Two-phase confirmation for destructive MCP actions. A first call issues
 * a short-lived token bound to a specific (action, target); the caller
 * must echo that token to actually execute. Prevents accidental or
 * mistargeted destructive operations from an agent.
 */
class ConfirmationGuard
{
    private const TTL_MINUTES = 5;

    /**
     * Issue a confirmation token and the preview of what will be affected.
     */
    public function challenge(string $action, string $targetId, array $preview): array
    {
        $token = Str::random(24);

        Cache::put(
            $this->key($token),
            ['action' => $action, 'target' => $targetId],
            now()->addMinutes(self::TTL_MINUTES),
        );

        return [
            'requires_confirmation' => true,
            'action' => $action,
            'target' => $targetId,
            'preview' => $preview,
            'confirmation_token' => $token,
            'message' => 'Ação destrutiva. Revise o preview e chame novamente incluindo confirmation_token '
                ."(válido por {$this->ttlMinutes()} min) para executar.",
        ];
    }

    /**
     * Validate and consume a token. Returns true only if it matches the
     * exact action+target; the token is single-use.
     */
    public function consume(string $action, string $targetId, ?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        $data = Cache::get($this->key($token));

        if ($data === null || $data['action'] !== $action || $data['target'] !== $targetId) {
            return false;
        }

        Cache::forget($this->key($token));

        return true;
    }

    public function ttlMinutes(): int
    {
        return self::TTL_MINUTES;
    }

    private function key(string $token): string
    {
        return 'mcp_confirm:'.hash('sha256', $token);
    }
}
