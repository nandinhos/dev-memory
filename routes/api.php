<?php

use App\Http\Controllers\McpController;
use App\Http\Controllers\MemoryController;
use Illuminate\Support\Facades\Route;

Route::post('/mcp', [McpController::class, 'handle'])->middleware('mcp.token')->name('mcp');

Route::get('/memories', [MemoryController::class, 'index']);
Route::post('/memories', [MemoryController::class, 'store']);
Route::get('/memories/search', [MemoryController::class, 'search']);
Route::get('/memories/{id}', [MemoryController::class, 'show']);
Route::put('/memories/{id}', [MemoryController::class, 'update']);
Route::delete('/memories/{id}', [MemoryController::class, 'destroy']);

Route::post('/memories/{id}/validate', [MemoryController::class, 'validate']);
Route::post('/memories/{id}/promote', [MemoryController::class, 'promoteToGlobal']);
Route::get('/stats', [MemoryController::class, 'stats']);
