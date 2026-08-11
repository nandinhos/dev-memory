<?php

namespace Tests\Unit;

use App\Enums\HarnessType;
use App\Services\HarnessInstallerGenerator;
use App\Services\HarnessProfileService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Symfony\Component\Process\Process;
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

    public function test_script_explicitly_refuses_to_overwrite_in_non_interactive_mode(): void
    {
        $service = app(HarnessProfileService::class);

        $profile = $service->capture(
            harness: HarnessType::CLAUDE_CODE,
            files: [
                ['path' => '~/.claude/CLAUDE.md', 'content' => '# Regras globais'],
            ],
            name: 'default',
        );

        $generator = app(HarnessInstallerGenerator::class);
        $script = $generator->generateScript($profile);

        $this->assertStringContainsString('FORCE_OVERWRITE', $script);
        $this->assertStringContainsString('--force', $script);
        $this->assertStringContainsString('ABORT', $script);
    }

    public function test_script_runs_end_to_end_via_bash_and_creates_files(): void
    {
        $bash = trim((string) shell_exec('command -v bash 2>/dev/null'));
        if ($bash === '') {
            $this->markTestSkipped('Requer bash disponível no host.');
        }

        $service = app(HarnessProfileService::class);

        $profile = $service->capture(
            harness: HarnessType::CODEX,
            files: [
                ['path' => 'AGENTS.md', 'content' => "# Regras Codex\n"],
            ],
            name: 'default',
        );

        $generator = app(HarnessInstallerGenerator::class);
        $script = $generator->generateScript($profile);

        $sandbox = sys_get_temp_dir().'/harness-installer-'.uniqid();
        mkdir($sandbox);
        $bashFile = $sandbox.'/install.sh';
        file_put_contents($bashFile, $script);

        $process = new Process(['bash', $bashFile]);
        $process->setWorkingDirectory($sandbox);
        $process->setTimeout(10);
        $process->run();

        $this->assertSame(0, $process->getExitCode(), 'STDERR: '.$process->getErrorOutput());
        $this->assertFileExists($sandbox.'/AGENTS.md');
        $this->assertSame("# Regras Codex\n", file_get_contents($sandbox.'/AGENTS.md'));

        $this->cleanSandbox($sandbox);
    }

    public function test_script_refuses_to_overwrite_existing_file_in_non_interactive_mode(): void
    {
        $bash = trim((string) shell_exec('command -v bash 2>/dev/null'));
        if ($bash === '') {
            $this->markTestSkipped('Requer bash disponível no host.');
        }

        $service = app(HarnessProfileService::class);

        $profile = $service->capture(
            harness: HarnessType::CODEX,
            files: [
                ['path' => 'AGENTS.md', 'content' => "# Versao nova\n"],
            ],
            name: 'default',
        );

        $generator = app(HarnessInstallerGenerator::class);
        $script = $generator->generateScript($profile);

        $sandbox = sys_get_temp_dir().'/harness-installer-'.uniqid();
        mkdir($sandbox);
        $existingContent = "# Versao original (local)\n";
        file_put_contents($sandbox.'/AGENTS.md', $existingContent);

        $bashFile = $sandbox.'/install.sh';
        file_put_contents($bashFile, $script);

        $process = new Process(['bash', $bashFile]);
        $process->setWorkingDirectory($sandbox);
        $process->setTimeout(10);
        $process->run();

        $this->assertNotSame(0, $process->getExitCode(), 'Script deveria ter abortado em modo não-interativo.');
        $this->assertSame($existingContent, file_get_contents($sandbox.'/AGENTS.md'));

        $this->cleanSandbox($sandbox);
    }

    public function test_script_overwrites_when_force_flag_is_passed_in_non_interactive_mode(): void
    {
        $bash = trim((string) shell_exec('command -v bash 2>/dev/null'));
        if ($bash === '') {
            $this->markTestSkipped('Requer bash disponível no host.');
        }

        $service = app(HarnessProfileService::class);

        $profile = $service->capture(
            harness: HarnessType::CODEX,
            files: [
                ['path' => 'AGENTS.md', 'content' => "# Versao nova\n"],
            ],
            name: 'default',
        );

        $generator = app(HarnessInstallerGenerator::class);
        $script = $generator->generateScript($profile);

        $sandbox = sys_get_temp_dir().'/harness-installer-'.uniqid();
        mkdir($sandbox);
        $existingContent = "# Versao original (local)\n";
        file_put_contents($sandbox.'/AGENTS.md', $existingContent);

        $bashFile = $sandbox.'/install.sh';
        file_put_contents($bashFile, $script);

        $process = new Process(['bash', $bashFile, '--force']);
        $process->setWorkingDirectory($sandbox);
        $process->setTimeout(10);
        $process->run();

        $this->assertSame(0, $process->getExitCode(), 'STDERR: '.$process->getErrorOutput());
        $this->assertSame("# Versao nova\n", file_get_contents($sandbox.'/AGENTS.md'));

        $this->cleanSandbox($sandbox);
    }

    private function cleanSandbox(string $sandbox): void
    {
        if (! is_dir($sandbox)) {
            return;
        }

        foreach (glob($sandbox.'/*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($sandbox);
    }
}
