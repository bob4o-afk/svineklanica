<?php

declare(strict_types=1);

namespace Modules\Notifications\Actions;

use App\Support\Logging\LoggingService;
use Modules\Notifications\Contracts\SubscriberRepository;
use Modules\Notifications\Jobs\NotifySubscribersJob;

/**
 * Single use case: broadcast a notification to every active subscriber. Dispatches
 * the fan-out as a queued Job so the request returns immediately (backend.md §3).
 */
final class NotifySubscribersAction
{
    public function __construct(
        private readonly SubscriberRepository $subscribers,
        private readonly LoggingService $log,
    ) {}

    /** @param list<string> $lines */
    public function execute(string $subject, array $lines = []): int
    {
        $count = $this->subscribers->countActive();

        NotifySubscribersJob::dispatch($subject, $lines);
        $this->log->info('subscribers.broadcast_queued', ['subject' => $subject, 'recipients' => $count]);

        return $count;
    }
}
