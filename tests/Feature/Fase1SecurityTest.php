<?php

namespace Tests\Feature;

use App\Enums\HarnessType;
use App\Enums\MemoryType;
use App\Http\Middleware\AuthenticateMcpToken;
use App\Livewire\MemoryDetail;
use App\Livewire\MemoryList;
use App\Models\ApiToken;
use App\Models\Capture;
use App\Models\Memory;
use App\Models\User;
use App\Services\HarnessProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class Fase1SecurityTest extends TestCase
{
    use RefreshDatabase;

    // ---------- RBAC ----------

    public function test_non_admin_gets_403_on_admin_routes(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]));

        foreach (['/admin/captures', '/admin/skills', '/admin/tokens', '/admin/harness', '/admin/settings', '/admin/skill-groups'] as $route) {
            $this->get($route)->assertForbidden();
        }
    }

    public function test_admin_can_reach_admin_routes(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get('/admin/tokens')->assertOk();
        $this->get('/admin/settings')->assertOk();
    }

    public function test_non_admin_cannot_delete_or_promote_memory(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]));
        $memory = Memory::create(['title' => 'x', 'description' => 'y', 'type' => MemoryType::LESSON])->fresh();

        Livewire::test(MemoryDetail::class, ['memory' => $memory])
            ->call('delete')
            ->assertForbidden();

        Livewire::test(MemoryDetail::class, ['memory' => $memory])
            ->call('promoteToGlobal')
            ->assertForbidden();

        Livewire::test(MemoryList::class)
            ->call('promoteMemory', $memory->id)
            ->assertForbidden();

        $this->assertDatabaseHas('memories', ['id' => $memory->id, 'scope' => 'project']);
    }

    public function test_admin_can_delete_memory(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $memory = Memory::create(['title' => 'x', 'description' => 'y', 'type' => MemoryType::LESSON])->fresh();

        Livewire::test(MemoryDetail::class, ['memory' => $memory])->call('delete');

        $this->assertSoftDeleted('memories', ['id' => $memory->id]);
    }

    public function test_make_admin_command_sets_is_admin(): void
    {
        $this->artisan('memory:make-admin', ['--email' => 'a@b.c', '--name' => 'A'])
            ->expectsQuestion('Senha (mínimo 8 caracteres)', 'senha-forte-123')
            ->assertSuccessful();

        $this->assertTrue(User::firstWhere('email', 'a@b.c')->is_admin);
    }

    // ---------- Criptografia de captures ----------

    public function test_capture_content_is_encrypted_at_rest(): void
    {
        $capture = Capture::create([
            'source_system' => 'test',
            'raw_content' => 'segredo-cru-AKIAxxx',
            'sanitized_content' => 'texto sanitizado',
            'idempotency_key' => 'k1',
            'status' => 'sanitized',
        ]);

        $raw = DB::table('captures')->where('id', $capture->id)->value('raw_content');

        $this->assertNotSame('segredo-cru-AKIAxxx', $raw);
        $this->assertStringNotContainsString('segredo-cru-AKIAxxx', $raw);
        // Mas o model decripta transparente:
        $this->assertSame('segredo-cru-AKIAxxx', $capture->fresh()->raw_content);
    }

    // ---------- Expiração de tokens MCP ----------

    public function test_expired_token_is_rejected_and_valid_accepted(): void
    {
        $user = User::factory()->create();
        [$expired] = ApiToken::issue($user, 'velho', now()->subDay());
        [$valid, $validPlain] = ApiToken::issue($user, 'novo', now()->addDay());

        // recupera o plaintext do expirado emitindo com plaintext conhecido não dá;
        // então testamos isExpired diretamente + o middleware via request fake.
        $this->assertTrue($expired->isExpired());
        $this->assertFalse($valid->isExpired());

        $mw = new AuthenticateMcpToken;
        $req = Request::create('/api/mcp', 'POST');
        $req->headers->set('Authorization', 'Bearer '.$validPlain);
        $resp = $mw->handle($req, fn () => response('ok'));
        $this->assertSame('ok', $resp->getContent());
    }

    public function test_expired_token_blocks_mcp_request(): void
    {
        $user = User::factory()->create();
        [, $plain] = ApiToken::issue($user, 'expira-ja', now()->subMinute());

        $this->postJson('/api/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'], [
            'Authorization' => 'Bearer '.$plain,
        ])->assertUnauthorized()->assertJsonFragment(['message' => 'Token de API expirado']);
    }

    // ---------- Allowlist de paths no harness ----------

    public function test_harness_capture_rejects_dangerous_paths(): void
    {
        $service = app(HarnessProfileService::class);

        $this->assertTrue($service->isSafePath('~/.claude/CLAUDE.md'));
        $this->assertTrue($service->isSafePath('.mcp.json'));
        $this->assertTrue($service->isSafePath('.claude/settings.json'));

        $this->assertFalse($service->isSafePath('/etc/cron.d/evil'));
        $this->assertFalse($service->isSafePath('~/.bashrc'));
        $this->assertFalse($service->isSafePath('../../etc/passwd'));
        $this->assertFalse($service->isSafePath('/root/.ssh/authorized_keys'));

        $this->expectException(\InvalidArgumentException::class);
        $service->capture(HarnessType::CLAUDE_CODE, [['path' => '/etc/passwd', 'content' => 'x']]);
    }
}
