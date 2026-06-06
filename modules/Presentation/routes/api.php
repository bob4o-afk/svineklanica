<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Presentation\Http\Controllers\EntityController;
use Modules\Presentation\Http\Controllers\FlagPostController;
use Modules\Presentation\Http\Controllers\InsightController;

// Loaded by PresentationServiceProvider under the 'api' middleware + '/api' prefix.
//
// The citizen-facing read API (the BFF the React app consumes). Public + read-only,
// but still rate-limited + abuse-guarded (security.md §1/§2). No write routes live here.
Route::middleware('throttle:public')->group(function (): void {
    // Flag-post feed + detail.
    Route::get('/flag-posts', [FlagPostController::class, 'index'])->name('flag-posts.index');
    Route::get('/flag-posts/{publicId}', [FlagPostController::class, 'show'])->name('flag-posts.show');

    // Entity profiles + global search.
    Route::get('/authorities/{publicId}', [EntityController::class, 'authority'])->name('authorities.show');
    Route::get('/companies/{eik}', [EntityController::class, 'company'])->name('companies.show');
    Route::get('/search', [EntityController::class, 'search'])->name('search');

    // Home hero counters (real totals, not hardcoded).
    Route::get('/stats', [InsightController::class, 'stats'])->name('stats');

    // Flagship visualisations + the map aggregate.
    Route::get('/price-series/{key}', [InsightController::class, 'priceSeries'])->name('price-series.show');
    Route::get('/regions/aggregate', [InsightController::class, 'regions'])->name('regions.aggregate');
    Route::get('/graphs/serial-winner/{publicId}', [InsightController::class, 'serialWinnerGraph'])->name('graphs.serial-winner');
});
