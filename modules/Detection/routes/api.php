<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Detection\Http\Controllers\Admin\FlagPostController as AdminFlagPostController;
use Modules\Detection\Http\Controllers\PublicFlagPostController;
use Modules\Detection\Http\Controllers\ReadModelStubController;

Route::middleware('throttle:120,1')->group(function (): void {
    Route::get('/flag-posts', [PublicFlagPostController::class, 'index'])->name('flag-posts.index');
    Route::get('/flag-posts/{publicId}', [PublicFlagPostController::class, 'show'])->name('flag-posts.show');

    Route::get('/authorities/{publicId}', fn () => abort(404))->name('authorities.show');
    Route::get('/companies/{eik}', fn () => abort(404))->name('companies.show');
    Route::get('/price-series/{key}', [ReadModelStubController::class, 'priceSeries'])->name('price-series.show');
    Route::get('/graphs/serial-winner/{publicId}', [ReadModelStubController::class, 'serialWinnerGraph'])
        ->name('graphs.serial-winner');
    Route::get('/regions/aggregate', [ReadModelStubController::class, 'regions'])->name('regions.aggregate');
    Route::get('/search', [ReadModelStubController::class, 'search'])->name('search');
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function (): void {
    Route::get('/flag-posts', [AdminFlagPostController::class, 'index'])->name('admin.flag-posts.index');
    Route::get('/flag-posts/{publicId}', [AdminFlagPostController::class, 'show'])->name('admin.flag-posts.show');
    Route::post('/flag-posts/{publicId}/approve', [AdminFlagPostController::class, 'approve'])
        ->name('admin.flag-posts.approve');
    Route::post('/flag-posts/{publicId}/reject', [AdminFlagPostController::class, 'reject'])
        ->name('admin.flag-posts.reject');
});
