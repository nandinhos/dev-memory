<?php

use App\Livewire\Admin\ApiTokens;
use App\Livewire\Admin\CapturesInbox;
use App\Livewire\Admin\HarnessProfiles;
use App\Livewire\Admin\SkillGroupsReview;
use App\Livewire\Admin\SkillsAdmin;
use App\Livewire\Admin\SystemSettings;
use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
use App\Livewire\MemoryDetail;
use App\Livewire\MemoryForm;
use App\Livewire\MemoryList;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/memories', MemoryList::class)->name('memories.index');
    Route::get('/memories/create', MemoryForm::class)->name('memories.create');
    Route::get('/memories/{memory}', MemoryDetail::class)->name('memories.show');
    Route::get('/memories/{memory}/edit', MemoryForm::class)->name('memories.edit');

    Route::middleware('admin')->group(function () {
        Route::get('/admin/captures', CapturesInbox::class)->name('admin.captures');
        Route::get('/admin/skill-groups', SkillGroupsReview::class)->name('admin.skill-groups');
        Route::get('/admin/skills', SkillsAdmin::class)->name('admin.skills');
        Route::get('/admin/tokens', ApiTokens::class)->name('admin.tokens');
        Route::get('/admin/harness', HarnessProfiles::class)->name('admin.harness');
        Route::get('/admin/settings', SystemSettings::class)->name('admin.settings');
    });

    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
