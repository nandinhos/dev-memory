<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemoryPruneTokensCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prunes_tokens_expired_beyond_threshold(): void
    {
        $user = User::factory()->create();

        // 1. Token expirado há 40 dias (deve ser deletado com --days=30)
        [$oldToken] = ApiToken::issue($user, 'token-muito-velho', now()->subDays(40));

        // 2. Token expirado há 10 dias (deve ser mantido com --days=30)
        [$recentExpired] = ApiToken::issue($user, 'token-recem-expirado', now()->subDays(10));

        // 3. Token ativo (deve ser mantido)
        [$activeToken] = ApiToken::issue($user, 'token-ativo', now()->addDays(30));

        // 4. Token sem expiração (deve ser mantido)
        [$neverExpires] = ApiToken::issue($user, 'token-sem-expiracao', null);

        $this->artisan('memory:prune-tokens', ['--days' => 30])
            ->expectsOutputToContain('1 token(s) expirado(s) há mais de 30 dias foram removidos.')
            ->assertSuccessful();

        $this->assertDatabaseMissing('api_tokens', ['id' => $oldToken->id]);
        $this->assertDatabaseHas('api_tokens', ['id' => $recentExpired->id]);
        $this->assertDatabaseHas('api_tokens', ['id' => $activeToken->id]);
        $this->assertDatabaseHas('api_tokens', ['id' => $neverExpires->id]);
    }

    public function test_it_reports_when_no_expired_tokens_are_found(): void
    {
        $this->artisan('memory:prune-tokens')
            ->expectsOutputToContain('Nenhum token expirado elegível para limpeza.')
            ->assertSuccessful();
    }
}
