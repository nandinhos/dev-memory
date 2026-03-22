<?php

use App\Livewire\Dashboard;
use App\Livewire\MemoryDetail;
use App\Livewire\MemoryForm;
use App\Livewire\MemoryList;
use Illuminate\Support\Facades\Route;

Route::get('/', Dashboard::class)->name('dashboard');
Route::get('/memories', MemoryList::class)->name('memories.index');
Route::get('/memories/create', MemoryForm::class)->name('memories.create');
Route::get('/memories/{memory}', MemoryDetail::class)->name('memories.show');
Route::get('/memories/{memory}/edit', MemoryForm::class)->name('memories.edit');
