<?php

use App\Http\Controllers\McpController;
use Illuminate\Support\Facades\Route;

// MCP é o único caminho oficial de acesso programático ao hub — tokenizado.
// throttle:120,1 = 120 req/min por token/IP: folga para uso agentico real,
// freio para brute-force de token e abuso de payload.
Route::post('/mcp', [McpController::class, 'handle'])
    ->middleware(['mcp.token', 'throttle:120,1'])
    ->name('mcp');
