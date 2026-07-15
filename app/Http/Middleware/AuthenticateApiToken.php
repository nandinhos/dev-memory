<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Standard REST token guard (plain 401 JSON) for the /api/memories
 * surface. Distinct from AuthenticateMcpToken only in the error shape:
 * the MCP endpoint answers in JSON-RPC, this one in plain REST JSON.
 */
class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = ApiToken::findByPlaintext($request->bearerToken());

        if ($token === null) {
            return response()->json(['message' => 'Token de API inválido ou ausente.'], 401);
        }

        $token->forceFill(['last_used_at' => now()])->save();
        $request->setUserResolver(fn () => $token->user);

        return $next($request);
    }
}
