<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Identity\Http\Controllers\AdminAuthController;

Route::prefix('admin')->group(function (): void {
    Route::post('/login', [AdminAuthController::class, 'login'])
        ->middleware('throttle:6,1')
        ->name('admin.auth.login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.auth.logout');
        Route::get('/me', [AdminAuthController::class, 'me'])->name('admin.auth.me');
    });
});
