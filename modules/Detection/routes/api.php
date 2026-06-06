<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Detection\Http\Controllers\FlagController;

// Loaded by DetectionServiceProvider under the 'api' middleware + '/api' prefix.

// Public red-flag feed — read-only, no auth, filtered by sphere/category/severity.
// Still rate-limited + abuse-guarded (security.md §1/§2).
Route::middleware('throttle:public')->group(function (): void {
    Route::get('/flags', [FlagController::class, 'index'])->name('flags.index');
    Route::get('/flags/{flag}', [FlagController::class, 'show'])->name('flags.show');
});
