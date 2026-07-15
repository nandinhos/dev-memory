<?php

use App\Http\Controllers\McpController;
use Illuminate\Support\Facades\Route;

// MCP é o único caminho oficial de acesso programático ao hub — tokenizado.
Route::post('/mcp', [McpController::class, 'handle'])->middleware('mcp.token')->name('mcp');
