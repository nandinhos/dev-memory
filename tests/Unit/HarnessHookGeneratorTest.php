<?php

namespace Tests\Unit;

use App\Enums\HarnessType;
use App\Services\HarnessHookGenerator;
use Tests\TestCase;

class HarnessHookGeneratorTest extends TestCase
{
    public function test_generates_valid_background_hook_script(): void
    {
        /** @var HarnessHookGenerator $generator */
        $generator = app(HarnessHookGenerator::class);

        $script = $generator->generateHookScript(
            harness: HarnessType::ANTIGRAVITY,
            mcpUrl: 'https://devmemory.fssdev.com.br/api/mcp',
            apiToken: 'test-token-123',
        );

        $this->assertStringContainsString('#!/usr/bin/env bash', $script);
        $this->assertStringContainsString('Google Antigravity', $script);
        $this->assertStringContainsString('memory_ingest', $script);
        $this->assertStringContainsString('hook_antigravity', $script);
        $this->assertStringContainsString('Authorization: Bearer test-token-123', $script);
        $this->assertStringContainsString('>/dev/null 2>&1 &', $script); // Execução não-bloqueante em background
    }
}
