<?php

declare(strict_types=1);

namespace Modules\Publishing;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Publishing\Contracts\PostRepository;
use Modules\Publishing\Repositories\EloquentPostRepository;

/**
 * Publishing bounded context: the public corruption feed (Posts) + IP-deduped
 * view counting (backend.md §14). Citizens read; admins author.
 */
class PublishingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PostRepository::class, EloquentPostRepository::class);
    }

    public function boot(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/routes/api.php');
    }
}
