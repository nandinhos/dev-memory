<?php

namespace Tests\Feature;

use App\Enums\MemoryScope;
use App\Enums\MemoryType;
use App\Enums\ValidationStatus;
use App\Livewire\Admin\ApiTokens;
use App\Models\ApiToken;
use App\Models\Memory;
use App\Models\User;
use App\Services\KnowledgeGraph\KnowledgeGraphProjector;
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
            ->assertJsonPath('result.serverInfo.name', 'dev-memory-mcp')
            ->assertJsonPath('result.capabilities.tools.listChanged', false)
            ->assertJsonPath('result.capabilities.resources.listChanged', false);
    }

    public function test_tools_list_exposes_the_expected_tools(): void
    {
        $token = $this->tokenFor(User::factory()->create());

        $response = $this->rpc($token, ['jsonrpc' => '2.0', 'method' => 'tools/list', 'id' => 2])
            ->assertOk();

        $tools = collect($response->json('result.tools'))->pluck('name');

        foreach (['memory_list', 'memory_search', 'memory_get', 'memory_related', 'memory_create', 'memory_stats', 'hub_briefing', 'memory_ingest'] as $expected) {
            $this->assertContains($expected, $tools);
        }

        $payload = json_decode($response->getContent());
        $toolsByName = collect($payload->result->tools)->keyBy('name');

        $this->assertInstanceOf(\stdClass::class, $toolsByName['memory_stats']->inputSchema->properties);
        $this->assertInstanceOf(\stdClass::class, $toolsByName['harness_list']->inputSchema->properties);
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

    public function test_memory_related_renders_mermaid_when_requested(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $source = Memory::factory()->create([
            'title' => 'Problema A',
            'type' => MemoryType::ERROR,
            'stack' => 'Laravel',
            'scope' => MemoryScope::GLOBAL,
            'validation_status' => ValidationStatus::VALIDATED,
        ]);

        $solution = Memory::factory()->create([
            'title' => 'Solução B',
            'type' => MemoryType::BEST_PRACTICE,
            'stack' => 'Laravel',
            'scope' => MemoryScope::GLOBAL,
            'validation_status' => ValidationStatus::VALIDATED,
        ]);

        $projector = app(KnowledgeGraphProjector::class);
        $projector->projectMemory($source);
        $projector->projectMemory($solution);

        $response = $this->rpc($token, [
            'jsonrpc' => '2.0',
            'method' => 'tools/call',
            'params' => [
                'name' => 'memory_related',
                'arguments' => [
                    'id' => $source->id,
                    'format' => 'mermaid',
                ],
            ],
            'id' => 99,
        ]);

        $response->assertOk();
        $content = $response->json('result.content.0.text');
        $this->assertNotNull($content);

        $result = json_decode($content, true);

        $this->assertArrayHasKey('mermaid', $result);
        $this->assertStringStartsWith('graph TD', $result['mermaid']);
        $this->assertStringContainsString('Problema A', $result['mermaid']);
        $this->assertStringContainsString('Solução B', $result['mermaid']);
    }

    public function test_harness_hook_script_generates_preflight_guard(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $response = $this->rpc($token, [
            'jsonrpc' => '2.0',
            'method' => 'tools/call',
            'params' => [
                'name' => 'harness_hook_script',
                'arguments' => [
                    'harness' => 'antigravity',
                    'hook_type' => 'preflight',
                ],
            ],
            'id' => 101,
        ]);

        $response->assertOk();
        $content = $response->json('result.content.0.text');
        $this->assertNotNull($content);

        $result = json_decode($content, true);

        $this->assertSame('antigravity', $result['harness']);
        $this->assertSame('preflight', $result['hook_type']);
        $this->assertContains('.agents/hooks/pre-flight-guard.sh', $result['hook_paths']);
        $this->assertStringContainsString('Pre-flight Guard Hook', $result['script']);
        $this->assertStringContainsString('hub_briefing', $result['script']);
    }
}
