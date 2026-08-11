<?php

namespace Tests\Unit;

use App\Enums\HarnessType;
use App\Services\HarnessHookGenerator;
use Symfony\Component\Process\Process;
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

    public function test_hook_script_encodes_content_with_jq_not_raw_interpolation(): void
    {
        /** @var HarnessHookGenerator $generator */
        $generator = app(HarnessHookGenerator::class);

        $script = $generator->generateHookScript(
            harness: HarnessType::ANTIGRAVITY,
            mcpUrl: 'https://devmemory.fssdev.com.br/api/mcp',
            apiToken: 'token-de-teste',
        );

        $this->assertStringContainsString('jq -n', $script);
        $this->assertStringContainsString('--arg content "$CONTENT"', $script);

        $this->assertStringNotContainsString('"content": "$CONTENT"', $script);
        $this->assertStringNotContainsString('"content": "$CONTENT",', $script);
    }

    public function test_hook_script_payload_is_valid_json_for_multiline_content(): void
    {
        foreach (['bash', 'jq'] as $bin) {
            $found = trim((string) shell_exec("command -v {$bin} 2>/dev/null"));
            if ($found === '') {
                $this->markTestSkipped("Requer {$bin} disponível no host.");
            }
        }

        /** @var HarnessHookGenerator $generator */
        $generator = app(HarnessHookGenerator::class);

        $script = $generator->generateHookScript(
            harness: HarnessType::ANTIGRAVITY,
            mcpUrl: 'http://127.0.0.1:1/never-called',
            apiToken: 'token-de-teste',
        );

        $multiline = "linha 1\nlinha 2 com \"aspas\" e \\barra";
        $capture = tempnam(sys_get_temp_dir(), 'hook-payload-');
        $bashFile = tempnam(sys_get_temp_dir(), 'hook-bash-');

        $curlStub = <<<'STUB'
curl() {
  local prev="" arg=""
  for arg in "$@"; do
    case "$prev" in
      -d|--data|--data-raw|--data-binary) printf '%s' "$arg" > "$HOOK_CAPTURE"; return 0;;
    esac
    prev="$arg"
  done
  return 0
}
export -f curl
STUB;
        // Aguarda o curl em background terminar antes de sair para que o
        // payload seja persistido no capture file.
        $scriptWithWait = rtrim($script)."\nwait\n";

        file_put_contents(
            $bashFile,
            'HOOK_CAPTURE='.escapeshellarg($capture)."\n".$curlStub."\n".$scriptWithWait,
        );

        $process = new Process(['bash', $bashFile]);
        $process->setInput($multiline);
        $process->setTimeout(10);
        $process->run();

        $payload = file_get_contents($capture);
        @unlink($capture);
        @unlink($bashFile);

        $this->assertNotEmpty($payload, 'O hook deveria ter gerado um payload. STDERR: '.$process->getErrorOutput());
        $decoded = json_decode(trim($payload), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('2.0', $decoded['jsonrpc']);
        $this->assertSame('memory_ingest', $decoded['params']['name']);
        $this->assertSame($multiline, $decoded['params']['arguments']['content']);
    }
}
