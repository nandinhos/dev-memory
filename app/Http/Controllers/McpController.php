<?php

namespace App\Http\Controllers;

use App\Mcp\MemoryMcpServer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Streamable HTTP transport for the MCP server (remote access from other
 * projects, authenticated by API token). Reuses MemoryMcpServer, the same
 * JSON-RPC handler used by the local stdio transport.
 */
class McpController extends Controller
{
    public function handle(Request $request, MemoryMcpServer $server): JsonResponse|Response
    {
        $payload = $request->json()->all();
        $method = $payload['method'] ?? null;

        // Notificações MCP (ex.: notifications/initialized) não esperam resposta.
        if (is_string($method) && str_starts_with($method, 'notifications/')) {
            return response()->noContent();
        }

        return response()->json($server->handle($payload));
    }
}
