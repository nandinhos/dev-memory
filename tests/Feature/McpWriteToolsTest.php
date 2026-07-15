<?php

namespace Tests\Feature;

use App\Enums\MemoryScope;
use App\Enums\MemoryType;
use App\Enums\ValidationStatus;
use App\Models\ApiToken;
use App\Models\Memory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpWriteToolsTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        [, $this->token] = ApiToken::issue(User::factory()->create(), 'writer');
    }

    private function callTool(string $tool, array $args = [], int $id = 1)
    {
        return $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
            ->postJson('/api/mcp', [
                'jsonrpc' => '2.0',
                'method' => 'tools/call',
                'params' => ['name' => $tool, 'arguments' => $args],
                'id' => $id,
            ]);
    }

    private function resultOf($response): array
    {
        return json_decode($response->json('result.content.0.text'), true);
    }

    private function makeMemory(array $overrides = []): Memory
    {
        return Memory::create(array_merge([
            'title' => 'Memória base',
            'description' => 'x',
            'type' => MemoryType::LESSON,
            'validation_status' => ValidationStatus::PENDING,
            'scope' => MemoryScope::PROJECT,
        ], $overrides));
    }

    public function test_memory_update_changes_fields(): void
    {
        $memory = $this->makeMemory();

        $result = $this->resultOf($this->callTool('memory_update', [
            'id' => $memory->id,
            'title' => 'Título novo',
            'type' => 'error',
        ]));

        $this->assertTrue($result['success']);
        $memory->refresh();
        $this->assertSame('Título novo', $memory->title);
        $this->assertSame(MemoryType::ERROR, $memory->type);
    }

    public function test_memory_validate_marks_validated(): void
    {
        $memory = $this->makeMemory();

        $this->resultOf($this->callTool('memory_validate', ['id' => $memory->id]));

        $this->assertSame(ValidationStatus::VALIDATED, $memory->fresh()->validation_status);
    }

    public function test_memory_promote_requires_validation(): void
    {
        $memory = $this->makeMemory(['validation_status' => ValidationStatus::PENDING]);

        $result = $this->resultOf($this->callTool('memory_promote', ['id' => $memory->id]));

        $this->assertArrayHasKey('error', $result);
        $this->assertSame(MemoryScope::PROJECT, $memory->fresh()->scope);
    }

    public function test_memory_promote_succeeds_when_validated(): void
    {
        $memory = $this->makeMemory(['validation_status' => ValidationStatus::VALIDATED]);

        $result = $this->resultOf($this->callTool('memory_promote', ['id' => $memory->id]));

        $this->assertTrue($result['success']);
        $this->assertSame(MemoryScope::GLOBAL, $memory->fresh()->scope);
    }

    public function test_memory_delete_requires_confirmation_first(): void
    {
        $memory = $this->makeMemory();

        $result = $this->resultOf($this->callTool('memory_delete', ['id' => $memory->id]));

        $this->assertTrue($result['requires_confirmation']);
        $this->assertArrayHasKey('confirmation_token', $result);
        $this->assertSame($memory->title, $result['preview']['title']);
        // Nada foi removido ainda
        $this->assertNotNull($memory->fresh());
    }

    public function test_memory_delete_executes_with_valid_token(): void
    {
        $memory = $this->makeMemory();

        $challenge = $this->resultOf($this->callTool('memory_delete', ['id' => $memory->id]));
        $token = $challenge['confirmation_token'];

        $result = $this->resultOf($this->callTool('memory_delete', [
            'id' => $memory->id,
            'confirmation_token' => $token,
        ]));

        $this->assertTrue($result['success']);
        $this->assertSoftDeleted('memories', ['id' => $memory->id]);
    }

    public function test_memory_delete_rejects_invalid_token(): void
    {
        $memory = $this->makeMemory();

        $result = $this->resultOf($this->callTool('memory_delete', [
            'id' => $memory->id,
            'confirmation_token' => 'token-falso',
        ]));

        $this->assertArrayHasKey('error', $result);
        $this->assertNotNull($memory->fresh());
    }

    public function test_confirmation_token_is_single_use_and_target_bound(): void
    {
        $a = $this->makeMemory(['title' => 'A']);
        $b = $this->makeMemory(['title' => 'B']);

        $token = $this->resultOf($this->callTool('memory_delete', ['id' => $a->id]))['confirmation_token'];

        // Token de A não serve para B
        $wrongTarget = $this->resultOf($this->callTool('memory_delete', [
            'id' => $b->id,
            'confirmation_token' => $token,
        ]));
        $this->assertArrayHasKey('error', $wrongTarget);

        // Consome para A
        $this->resultOf($this->callTool('memory_delete', ['id' => $a->id, 'confirmation_token' => $token]));
        // Reuso do mesmo token falha (single-use)
        $reuse = $this->resultOf($this->callTool('memory_delete', ['id' => $a->id, 'confirmation_token' => $token]));
        $this->assertArrayHasKey('error', $reuse);
    }
}
