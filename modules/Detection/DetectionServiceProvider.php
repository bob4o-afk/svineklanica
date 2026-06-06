<?php

declare(strict_types=1);

namespace Modules\Detection;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Detection\Console\Commands\AnalyzeIngestCommand;
use Modules\Detection\Console\Commands\CorruptionTaxCommand;
use Modules\Detection\Console\Commands\DetectRunCommand;
use Modules\Detection\Contracts\FlagRepository;
use Modules\Detection\Detectors\DetectorRegistry;
use Modules\Detection\Repositories\EloquentFlagRepository;

/**
 * Detection bounded context: the red-flag detectors (CLAUDE.md §1.1) and the
 * Flag they emit. Each detector is a queued, re-runnable job; the UI reads
 * precomputed flags (backend.md §11), filtered by Sphere → Category → Severity.
 */
class DetectionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FlagRepository::class, EloquentFlagRepository::class);
        $this->app->singleton(DetectorRegistry::class);
    }

    public function boot(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/routes/api.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                DetectRunCommand::class,
                CorruptionTaxCommand::class,
                AnalyzeIngestCommand::class,
            ]);
        }
    }
}
