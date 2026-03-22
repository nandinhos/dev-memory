<?php

namespace App\Console\Commands;

use App\Mcp\MemoryMcpServer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class McpServeCommand extends Command
{
    protected $signature = 'mcp:serve {--host=0.0.0.0} {--port=3000}';

    protected $description = 'Inicia o servidor MCP para o Dev Memory';

    private ?MemoryMcpServer $server = null;

    public function handle(): int
    {
        $host = $this->option('host');
        $port = $this->option('port');

        $this->info("Iniciando servidor MCP em http://{$host}:{$port}");
        $this->info("Pressione Ctrl+C para encerrar");

        $this->server = new MemoryMcpServer();

        $socket = stream_socket_server("tcp://{$host}:{$port}", $errno, $errorMsg);
        
        if (!$socket) {
            $this->error("Erro ao criar socket: {$errorMsg}");
            return self::FAILURE;
        }

        stream_set_blocking($socket, false);

        $this->info("Servidor MCP pronto!");
        Log::info("MCP Server started on {$host}:{$port}");

        while (true) {
            $client = @stream_socket_accept($socket, 0.1);
            
            if ($client) {
                $this->handleClient($client);
            }
            
            usleep(10000);
        }

        return self::SUCCESS;
    }

    private function handleClient($client): void
    {
        $data = fread($client, 8192);
        
        if (empty($data)) {
            fclose($client);
            return;
        }

        $request = json_decode($data, true);

        if (!$request) {
            fwrite($client, json_encode([
                'jsonrpc' => '2.0',
                'error' => ['code' => -32700, 'message' => 'Parse error']
            ]));
            fclose($client);
            return;
        }

        $response = $this->server->handle($request);
        
        fwrite($client, json_encode($response));
        fclose($client);
    }
}