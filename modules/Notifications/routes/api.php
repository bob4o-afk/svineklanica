<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Notifications\Http\Controllers\Admin\BroadcastController;
use Modules\Notifications\Http\Controllers\SubscriptionController;

// Loaded by NotificationsServiceProvider under the 'api' middleware + '/api' prefix.

// Public opt-in / one-click unsubscribe — tightly rate-limited (security.md §2).
Route::middleware('throttle:contact')->group(function (): void {
    Route::post('/subscribe', [SubscriptionController::class, 'subscribe'])->name('subscribe');
    Route::get('/unsubscribe/{token}', [SubscriptionController::class, 'unsubscribe'])->name('unsubscribe');
});

// Admin: broadcast to all subscribers — authenticated + admin only.
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function (): void {
    Route::post('/notify-subscribers', [BroadcastController::class, 'store'])->name('admin.notify-subscribers');
});
