<?php

declare(strict_types=1);

namespace Modules\Publishing;

use Illuminate\Support\ServiceProvider;

/**
 * Publishing bounded context: the public corruption feed (Posts) + IP-deduped
 * view counting (backend.md §14). Citizens read; admins author. Repository
 * bindings are added as they land.
 */
class PublishingServiceProvider extends ServiceProvider
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
