<?php

declare(strict_types=1);

namespace Modules\Notifications;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Notifications\Console\SendTestMailCommand;
use Modules\Notifications\Contracts\SubscriberRepository;
use Modules\Notifications\Repositories\EloquentSubscriberRepository;

/**
 * Notifications bounded context: queued transactional email (backend.md §1/§3),
 * sent via the configured mailer (Resend in prod, Mailpit locally), plus the
 * subscriber list + broadcast endpoints.
 */
final class NotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SubscriberRepository::class, EloquentSubscriberRepository::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'notifications');

        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/routes/api.php');

        if ($this->app->runningInConsole()) {
            $this->commands([SendTestMailCommand::class]);
        }
    }
}
