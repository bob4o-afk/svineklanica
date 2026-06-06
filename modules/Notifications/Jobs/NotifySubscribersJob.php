<?php

declare(strict_types=1);

namespace Modules\Notifications\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Modules\Notifications\Contracts\SubscriberRepository;
use Modules\Notifications\Mail\GenericNotificationMail;
use Modules\Notifications\Models\Subscriber;

/**
 * Fan-out (backend.md §3): broadcasting to every subscriber is slow, so it runs
 * off-request. It queues ONE mail per subscriber (each its own queue entry), so a
 * single bad address never blocks the rest, and appends a one-click unsubscribe.
 */
final class NotifySubscribersJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    /** @param list<string> $lines */
    public function __construct(
        public readonly string $subject,
        public readonly array $lines = [],
    ) {}

    public function handle(SubscriberRepository $subscribers): void
    {
        $subscribers->eachActive(function (Subscriber $subscriber): void {
            $lines = [
                ...$this->lines,
                '— — —',
                'Отписване: '.url('/api/unsubscribe/'.$subscriber->unsubscribe_token),
            ];

            Mail::to($subscriber->email)->queue(new GenericNotificationMail($this->subject, $lines));
        });
    }
}
