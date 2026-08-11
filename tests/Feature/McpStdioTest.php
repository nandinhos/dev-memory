<?php

namespace Tests\Feature;

use Symfony\Component\Process\Process;
use Tests\TestCase;

class McpStdioTest extends TestCase
{
    public function test_stdio_preserves_numeric_ids_and_does_not_respond_to_notifications(): void
    {
        $initialize = json_encode([
            'jsonrpc' => '2.0',
            'id' => 0,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [],
                'clientInfo' => ['name' => 'teste', 'version' => '1.0.0'],
            ],
        ], JSON_THROW_ON_ERROR);
        $initialized = json_encode([
            'jsonrpc' => '2.0',
            'method' => 'notifications/initialized',
        ], JSON_THROW_ON_ERROR);

        $process = new Process([PHP_BINARY, 'artisan', 'mcp:serve'], base_path());
        $process->setInput($initialize."\n".$initialized."\n");
        $process->mustRun();

        $responses = array_values(array_filter(explode("\n", trim($process->getOutput()))));

        $this->assertCount(1, $responses);

        $response = json_decode($responses[0], true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $response['id']);
        $this->assertSame('dev-memory-mcp', $response['result']['serverInfo']['name']);
    }
}
