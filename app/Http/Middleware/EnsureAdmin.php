<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe as telas e ações de administração do pipeline (/admin/*) a
 * usuários com is_admin. Registrado também como persistent middleware do
 * Livewire (AppServiceProvider) para valer nas atualizações de componente,
 * não só no load da página.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->is_admin === true, 403, 'Acesso restrito a administradores.');

        return $next($request);
    }
}
