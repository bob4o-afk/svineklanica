<?php

declare(strict_types=1);

namespace Modules\Presentation;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Presentation\Console\Commands\FlushFlagViewsCommand;
use Modules\Presentation\Contracts\PresentationRepository;
use Modules\Presentation\Repositories\EloquentPresentationRepository;

/**
 * Presentation bounded context: the citizen-facing READ API (a BFF) that projects the
 * Procurement + Detection domain into the exact contract the React app consumes
 * (apps/web `contract.ts`). It is the one place allowed to read across modules —
 * that read coupling is the point of a presentation seam.
 */
final class PresentationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PresentationRepository::class, EloquentPresentationRepository::class);
    }

    public function boot(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/routes/api.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                FlushFlagViewsCommand::class,
            ]);
        }
    }
}
