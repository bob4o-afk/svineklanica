<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Publishing\Http\Controllers\Admin\PostController;
use Modules\Publishing\Http\Controllers\PublicPostController;

// Loaded by PublishingServiceProvider under the 'api' middleware + '/api' prefix.

// Public corruption feed — read-only, no auth, still rate-limited (security.md §2).
Route::middleware('throttle:120,1')->group(function (): void {
    Route::get('/posts', [PublicPostController::class, 'index'])->name('posts.index');
    Route::get('/posts/{post}', [PublicPostController::class, 'show'])->name('posts.show');
});

// Admin authoring — authenticated + admin only.
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function (): void {
    Route::post('/posts', [PostController::class, 'store'])->name('admin.posts.store');
    Route::put('/posts/{post}', [PostController::class, 'update'])->name('admin.posts.update');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('admin.posts.destroy');
});
