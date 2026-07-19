<?php

namespace Tests\Feature;

use App\Enums\HarnessType;
use App\Models\ApiToken;
use App\Models\HarnessProfile;
use App\Models\User;
use App\Services\HarnessProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HarnessProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        [, $this->token] = ApiToken::issue(User::factory()->create(), 'harness');
    }

    private function callTool(string $tool, array $args = [])
    {
        return $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
            ->postJson('/api/mcp', [
                'jsonrpc' => '2.0',
                'method' => 'tools/call',
                'params' => ['name' => $tool, 'arguments' => $args],
                'id' => 1,
            ]);
    }

    private function resultOf($response): array
    {
        return json_decode($response->json('result.content.0.text'), true);
    }

    public function test_capture_sanitizes_secrets_and_stores_files(): void
    {
        $service = app(HarnessProfileService::class);

        $profile = $service->capture(HarnessType::CLAUDE_CODE, [
            ['path' => '~/.claude/CLAUDE.md', 'content' => 'Minhas instruções globais.'],
            ['path' => '~/.claude/settings.json', 'content' => '{"env": {"ANTHROPIC_API_KEY": "sk-ant-secretvalue12345"}}'],
        ]);

        $this->assertSame('1.0.0', $profile->version);
        $this->assertCount(2, $profile->files);

        $settings = collect($profile->files)->firstWhere('path', '~/.claude/settings.json');
        $this->assertStringNotContainsString('sk-ant-secretvalue12345', $settings['content']);
        $this->assertNotEmpty($settings['redactions']);
    }

    public function test_recapture_bumps_patch_version(): void
    {
        $service = app(HarnessProfileService::class);

        $service->capture(HarnessType::CLAUDE_CODE, [['path' => '.mcp.json', 'content' => 'x']]);
        $second = $service->capture(HarnessType::CLAUDE_CODE, [['path' => '.mcp.json', 'content' => 'y']]);

        $this->assertSame('1.0.1', $second->version);
        $this->assertSame(1, HarnessProfile::count());
    }

    public function test_provision_plan_lists_steps_and_flags_secrets(): void
    {
        $service = app(HarnessProfileService::class);

        $profile = $service->capture(HarnessType::CLAUDE_CODE, [
            ['path' => '~/.claude/CLAUDE.md', 'content' => 'instruções limpas'],
            ['path' => '~/.claude/settings.json', 'content' => 'DB_PASSWORD=segredo123'],
        ]);

        $plan = $service->provisionPlan($profile);

        $this->assertCount(2, $plan['steps']);
        $this->assertSame('write_file', $plan['steps'][0]['action']);

        $settingsStep = collect($plan['steps'])->firstWhere('path', '~/.claude/settings.json');
        $this->assertTrue($settingsStep['had_secrets']);
        $cleanStep = collect($plan['steps'])->firstWhere('path', '~/.claude/CLAUDE.md');
        $this->assertFalse($cleanStep['had_secrets']);
    }

    public function test_harness_paths_tool_returns_recommended_paths(): void
    {
        $result = $this->resultOf($this->callTool('harness_paths', ['harness' => 'claude-code']));

        $this->assertContains('~/.claude/CLAUDE.md', $result['recommended_paths']);
    }

    public function test_capture_and_provision_over_mcp(): void
    {
        $capture = $this->resultOf($this->callTool('harness_capture', [
            'harness' => 'claude-code',
            'files' => [
                ['path' => '~/.claude/CLAUDE.md', 'content' => 'meu jeito de codar'],
                ['path' => '.mcp.json', 'content' => '{"token": "abc123secretxyz"}'],
            ],
        ]));

        $this->assertTrue($capture['success']);
        $this->assertSame(2, $capture['files_stored']);

        $list = $this->resultOf($this->callTool('harness_list'));
        $this->assertCount(1, $list['profiles']);

        $plan = $this->resultOf($this->callTool('harness_provision', ['harness' => 'claude-code']));
        $this->assertCount(2, $plan['steps']);
        $this->assertArrayHasKey('notes', $plan);
    }

    public function test_provision_unknown_profile_returns_error(): void
    {
        $result = $this->resultOf($this->callTool('harness_provision', ['harness' => 'claude-code']));

        $this->assertArrayHasKey('error', $result);
    }

    public function test_invalid_harness_is_rejected(): void
    {
        $result = $this->resultOf($this->callTool('harness_capture', [
            'harness' => 'inexistente',
            'files' => [['path' => 'x', 'content' => 'y']],
        ]));

        $this->assertArrayHasKey('error', $result);
    }
}
