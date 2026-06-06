<?php

declare(strict_types=1);

namespace Modules\Detection;

use Illuminate\Support\ServiceProvider;

/**
 * Detection bounded context: the red-flag detectors (CLAUDE.md §1.1) and the
 * Flag they emit. Each detector is a queued, re-runnable job; the UI reads
 * precomputed flags (backend.md §11).
 */
class DetectionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
