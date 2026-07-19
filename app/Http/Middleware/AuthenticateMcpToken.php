<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMcpToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = ApiToken::findByPlaintext($request->bearerToken());

        if ($token === null || $token->isExpired()) {
            $message = $token?->isExpired() ? 'Token de API expirado' : 'Token de API inválido ou ausente';

            return response()->json([
                'jsonrpc' => '2.0',
                'error' => ['code' => -32001, 'message' => $message],
                'id' => null,
            ], 401);
        }

        $token->forceFill(['last_used_at' => now()])->save();
        $request->setUserResolver(fn () => $token->user);

        return $next($request);
    }
}
