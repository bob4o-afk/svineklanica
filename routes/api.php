<?php

declare(strict_types=1);

use App\Http\Controllers\VersionController;
use Illuminate\Support\Facades\Route;

// Root API routes. Each module registers its own routes from its service
// provider (Route::prefix('api')->middleware('api')->group(...)), keeping
// bounded contexts self-contained (backend.md §1).

// Invokable controller (not a closure) so `route:cache` can serialize it.
Route::get('/version', VersionController::class)->name('api.version');
