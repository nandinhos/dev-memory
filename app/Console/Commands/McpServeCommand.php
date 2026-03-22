<?php

namespace App\Console\Commands;

use App\Mcp\MemoryMcpServer;
use Illuminate\Console\Command;

class McpServeCommand extends Command
{
    protected $signature = 'mcp:serve';

    protected $description = 'Inicia o servidor MCP para o Dev Memory (STDIO/JSON-RPC)';

    public function handle(): int
    {
        $server = new MemoryMcpServer();

        $stdin  = fopen('php://stdin', 'r');
        $stdout = fopen('php://stdout', 'w');

        stream_set_blocking($stdin, true);

        while (! feof($stdin)) {
            $line = fgets($stdin);

            if ($line === false || trim($line) === '') {
                continue;
            }

            $request = json_decode(trim($line), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $response = [
                    'jsonrpc' => '2.0',
                    'id'      => null,
                    'error'   => ['code' => -32700, 'message' => 'Parse error'],
                ];
            } else {
                $response = $server->handle($request);
            }

            fwrite($stdout, json_encode($response) . "\n");
            fflush($stdout);
        }

        return self::SUCCESS;
    }
}
