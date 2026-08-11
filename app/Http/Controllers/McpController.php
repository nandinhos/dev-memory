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
        $response = $server->handle($payload, $request->attributes->get('mcp_token'));

        if ($response === null) {
            return response()->noContent();
        }

        return response()->json($response);
    }
}
