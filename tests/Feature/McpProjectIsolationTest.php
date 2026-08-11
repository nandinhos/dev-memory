<?php

namespace Tests\Feature;

use App\Enums\MemoryScope;
use App\Enums\MemoryType;
use App\Models\ApiToken;
use App\Models\Capture;
use App\Models\Memory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpProjectIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function rpc(string $token, string $tool, array $arguments = []): array
    {
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson('/api/mcp', [
                'jsonrpc' => '2.0',
                'method' => 'tools/call',
                'params' => ['name' => $tool, 'arguments' => $arguments],
                'id' => 1,
            ]);

        return json_decode($response->json('result.content.0.text'), true);
    }

    public function test_project_token_only_reads_its_project_and_global_memories(): void
    {
        [$tokenA, $plainA] = ApiToken::issue(User::factory()->create(['is_admin' => false]), 'projeto-a');
        [$tokenB] = ApiToken::issue(User::factory()->create(['is_admin' => false]), 'projeto-b');

        $own = Memory::factory()->create([
            'title' => 'Visível A',
            'scope' => MemoryScope::PROJECT,
            'project_id' => $tokenA->project_id,
        ]);
        $foreign = Memory::factory()->create([
            'title' => 'Privada B',
            'scope' => MemoryScope::PROJECT,
            'project_id' => $tokenB->project_id,
        ]);
        $global = Memory::factory()->create([
            'title' => 'Global',
            'scope' => MemoryScope::GLOBAL,
            'project_id' => null,
        ]);

        $list = $this->rpc($plainA, 'memory_list');
        $ids = collect($list['memories'])->pluck('id')->all();

        $this->assertContains($own->id, $ids);
        $this->assertContains($global->id, $ids);
        $this->assertNotContains($foreign->id, $ids);
        $this->assertSame(['error' => 'Memória não encontrada'], $this->rpc($plainA, 'memory_get', ['id' => $foreign->id]));
    }

    public function test_project_argument_is_provenance_not_an_authorization_boundary(): void
    {
        [$token, $plain] = ApiToken::issue(User::factory()->create(['is_admin' => false]), 'projeto-a');

        $created = $this->rpc($plain, 'memory_create', [
            'title' => 'Memória do token',
            'description' => 'O escopo vem da credencial.',
            'type' => MemoryType::LESSON->value,
            'scope' => MemoryScope::PROJECT->value,
        ]);

        $this->assertTrue($created['success']);
        $this->assertSame($token->project_id, Memory::findOrFail($created['id'])->project_id);

        $ingested = $this->rpc($plain, 'memory_ingest', [
            'content' => 'Falha de teste resolvida.',
            'project' => 'valor-controlado-pelo-cliente',
        ]);

        $this->assertSame($token->project_id, Capture::findOrFail($ingested['capture_id'])->project_id);
        $this->assertSame('valor-controlado-pelo-cliente', Capture::findOrFail($ingested['capture_id'])->source_project);
    }

    public function test_legacy_token_without_project_or_global_grant_fails_closed(): void
    {
        [$token, $plain] = ApiToken::issue(User::factory()->create(['is_admin' => true]), 'legado');
        $token->update(['project_id' => null]);

        $this->assertSame(
            ['error' => 'Token MCP sem projeto associado. Emita um novo token vinculado a um projeto.'],
            $this->rpc($plain, 'memory_list'),
        );
    }
}
