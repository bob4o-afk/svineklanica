<?php

declare(strict_types=1);

namespace Modules\Notifications\Actions;

use App\Support\Logging\LoggingService;
use Illuminate\Support\Facades\Mail;
use Modules\Notifications\Mail\GenericNotificationMail;

/**
 * Single use case: queue a notification email (backend.md §2/§3).
 * `->queue()` pushes it onto the Redis queue — the caller never blocks on SMTP/API.
 */
final class SendNotificationAction
{
    public function __construct(private readonly LoggingService $log) {}

    /** @param list<string> $lines */
    public function execute(string $to, string $subject, array $lines = []): void
    {
        Mail::to($to)->queue(new GenericNotificationMail($subject, $lines));

        $this->log->info('notification.queued', ['to' => $to, 'subject' => $subject]);
    }
}
