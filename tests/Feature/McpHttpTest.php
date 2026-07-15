<?php

namespace Tests\Feature;

use App\Livewire\Admin\ApiTokens;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class McpHttpTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        [, $plain] = ApiToken::issue($user, 'projeto-teste');

        return $plain;
    }

    private function rpc(string $token, array $body)
    {
        return $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson('/api/mcp', $body);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->postJson('/api/mcp', ['jsonrpc' => '2.0', 'method' => 'initialize', 'id' => 1])
            ->assertStatus(401)
            ->assertJsonPath('error.code', -32001);
    }

    public function test_invalid_token_is_rejected(): void
    {
        $this->rpc('token-que-nao-existe', ['jsonrpc' => '2.0', 'method' => 'initialize', 'id' => 1])
            ->assertStatus(401);
    }

    public function test_initialize_returns_server_info(): void
    {
        $token = $this->tokenFor(User::factory()->create());

        $this->rpc($token, ['jsonrpc' => '2.0', 'method' => 'initialize', 'id' => 1])
            ->assertOk()
            ->assertJsonPath('result.serverInfo.name', 'dev-memory-mcp');
    }

    public function test_tools_list_exposes_the_expected_tools(): void
    {
        $token = $this->tokenFor(User::factory()->create());

        $response = $this->rpc($token, ['jsonrpc' => '2.0', 'method' => 'tools/list', 'id' => 2])
            ->assertOk();

        $tools = collect($response->json('result.tools'))->pluck('name');

        foreach (['memory_list', 'memory_search', 'memory_get', 'memory_create', 'memory_stats', 'hub_briefing', 'memory_ingest'] as $expected) {
            $this->assertContains($expected, $tools);
        }
    }

    public function test_tools_call_stats_executes(): void
    {
        $token = $this->tokenFor(User::factory()->create());

        $this->rpc($token, [
            'jsonrpc' => '2.0',
            'method' => 'tools/call',
            'params' => ['name' => 'memory_stats', 'arguments' => []],
            'id' => 3,
        ])->assertOk()->assertJsonStructure(['result']);
    }

    public function test_notifications_return_no_content(): void
    {
        $token = $this->tokenFor(User::factory()->create());

        $this->rpc($token, ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'])
            ->assertNoContent();
    }

    public function test_token_use_updates_last_used_at(): void
    {
        $user = User::factory()->create();
        [$model, $plain] = ApiToken::issue($user, 'x');
        $this->assertNull($model->last_used_at);

        $this->rpc($plain, ['jsonrpc' => '2.0', 'method' => 'initialize', 'id' => 1])->assertOk();

        $this->assertNotNull($model->fresh()->last_used_at);
    }

    public function test_token_management_issue_and_revoke(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(ApiTokens::class)
            ->set('name', 'meu-projeto')
            ->call('create')
            ->assertSet('name', '');

        $this->assertNotNull($component->get('plaintext'));
        $this->assertSame(1, ApiToken::where('user_id', $user->id)->count());

        $token = ApiToken::where('user_id', $user->id)->first();

        Livewire::actingAs($user)->test(ApiTokens::class)
            ->call('revoke', $token->id);

        $this->assertSame(0, ApiToken::where('user_id', $user->id)->count());
    }

    public function test_users_only_see_and_revoke_their_own_tokens(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        [$token] = ApiToken::issue($owner, 'do-dono');

        Livewire::actingAs($other)->test(ApiTokens::class)
            ->call('revoke', $token->id);

        $this->assertSame(1, ApiToken::where('user_id', $owner->id)->count());
    }
}
