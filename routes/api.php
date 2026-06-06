<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Root API routes. Each module registers its own routes from its service
// provider (Route::prefix('api')->middleware('api')->group(...)), keeping
// bounded contexts self-contained (backend.md §1).

Route::get('/version', fn () => ['name' => config('app.name'), 'api' => 'v1'])
    ->name('api.version');
