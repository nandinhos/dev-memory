<?php

namespace Tests\Feature;

use App\Jobs\CurateCaptureJob;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ContinuousIngestTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_ingests_capture_from_hook_and_dispatches_curation_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [, $plainToken] = ApiToken::issue($user, 'hook-test-token');

        $payload = [
            'jsonrpc' => '2.0',
            'method' => 'tools/call',
            'params' => [
                'name' => 'memory_ingest',
                'arguments' => [
                    'content' => 'Error: Undefined variable $foo in Controller.php line 42',
                    'source' => 'hook_antigravity',
                    'trigger' => 'post_tool_use',
                    'project' => 'my-app',
                ],
            ],
            'id' => 1,
        ];

        $response = $this->withHeader('Authorization', "Bearer {$plainToken}")
            ->postJson('/api/mcp', $payload);

        $response->assertStatus(200);

        $rpcResult = json_decode($response->json('result.content.0.text'), true);
        $this->assertFalse($rpcResult['deduplicated']);
        $this->assertNotEmpty($rpcResult['capture_id']);

        $this->assertDatabaseHas('captures', [
            'source_system' => 'hook_antigravity',
            'trigger_event' => 'post_tool_use',
            'source_project' => 'my-app',
        ]);

        Queue::assertPushed(CurateCaptureJob::class);
    }
}
