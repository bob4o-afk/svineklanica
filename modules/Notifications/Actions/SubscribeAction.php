<?php

declare(strict_types=1);

namespace Modules\Notifications\Actions;

use App\Support\Logging\LoggingService;
use Modules\Notifications\Contracts\SubscriberRepository;
use Modules\Notifications\Models\Subscriber;

/** Single use case: opt an e-mail into notifications (idempotent). */
final class SubscribeAction
{
    public function __construct(
        private readonly SubscriberRepository $subscribers,
        private readonly LoggingService $log,
    ) {}

    public function execute(string $email): Subscriber
    {
        $subscriber = $this->subscribers->subscribe($email);
        $this->log->info('subscriber.subscribed', ['public_id' => $subscriber->public_id]);

        return $subscriber;
    }
}
