<?php

namespace Tests\Feature;

use App\Enums\HarnessType;
use App\Services\HarnessProfileService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class HarnessInstallerRouteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_downloads_installer_script_via_http_route(): void
    {
        /** @var HarnessProfileService $service */
        $service = app(HarnessProfileService::class);

        $service->capture(
            harness: HarnessType::ANTIGRAVITY,
            files: [
                ['path' => '~/.gemini/config/AGENTS.md', 'content' => '# Global Rules'],
            ],
            name: 'default',
        );

        $response = $this->get('/install/harness/antigravity/default');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
        $response->assertSee('Google Antigravity');
        $response->assertSee('# Global Rules');
    }

    public function test_returns_404_on_unknown_harness_or_profile(): void
    {
        $response = $this->get('/install/harness/unknown/default');
        $response->assertStatus(404);
    }
}
