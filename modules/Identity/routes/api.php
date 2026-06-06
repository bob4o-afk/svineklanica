<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Identity\Http\Controllers\Admin\SecurityController;
use Modules\Identity\Http\Controllers\AuthController;

// Loaded by IdentityServiceProvider under the 'api' middleware + '/api' prefix.

// Login is tightly throttled (security.md §2) — brute-force defense.
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login')
    ->name('auth.login');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/user', [AuthController::class, 'me'])->name('auth.me');
});

// Admin security console — authenticated + admin only. The IP allow-list is
// read-only (env is the single source of truth, security.md §4).
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function (): void {
    Route::get('/security/whitelist', [SecurityController::class, 'whitelist'])
        ->name('admin.security.whitelist');
});
