<?php

namespace Tests\Unit;

use App\Enums\HarnessType;
use Tests\TestCase;

class HarnessTypeTest extends TestCase
{
    public function test_harness_type_cases_labels_and_paths(): void
    {
        $this->assertEquals('Claude Code', HarnessType::CLAUDE_CODE->label());
        $this->assertEquals('OpenAI Codex', HarnessType::CODEX->label());
        $this->assertEquals('Google Antigravity', HarnessType::ANTIGRAVITY->label());
        $this->assertEquals('Hermes CLI', HarnessType::HERMES->label());

        $this->assertContains('~/.codex/config.toml', HarnessType::CODEX->recommendedPaths());
        $this->assertContains('~/.gemini/config/AGENTS.md', HarnessType::ANTIGRAVITY->recommendedPaths());
        $this->assertContains('~/.hermes/config.json', HarnessType::HERMES->recommendedPaths());
    }
}
