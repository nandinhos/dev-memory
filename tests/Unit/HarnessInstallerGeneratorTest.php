<?php

namespace Tests\Unit;

use App\Enums\HarnessType;
use App\Services\HarnessInstallerGenerator;
use App\Services\HarnessProfileService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class HarnessInstallerGeneratorTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_generates_valid_bash_installer_script(): void
    {
        /** @var HarnessProfileService $service */
        $service = app(HarnessProfileService::class);

        $profile = $service->capture(
            harness: HarnessType::CODEX,
            files: [
                ['path' => '~/.codex/config.toml', 'content' => 'model = "gpt-5.6-sol"'],
            ],
            name: 'default',
        );

        /** @var HarnessInstallerGenerator $generator */
        $generator = app(HarnessInstallerGenerator::class);
        $script = $generator->generateScript($profile);

        $this->assertStringContainsString('#!/usr/bin/env bash', $script);
        $this->assertStringContainsString('OpenAI Codex', $script);
        $this->assertStringContainsString('model = "gpt-5.6-sol"', $script);
        $this->assertStringContainsString('set -euo pipefail', $script);
    }
}
