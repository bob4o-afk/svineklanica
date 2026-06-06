<?php

declare(strict_types=1);

namespace Modules\Notifications\Repositories;

use Closure;
use Illuminate\Support\Str;
use Modules\Notifications\Contracts\SubscriberRepository;
use Modules\Notifications\Models\Subscriber;

final class EloquentSubscriberRepository implements SubscriberRepository
{
    public function subscribe(string $email): Subscriber
    {
        $subscriber = Subscriber::query()->firstOrNew(['email' => $email]);

        if ($subscriber->unsubscribe_token === null) {
            $subscriber->unsubscribe_token = Str::random(64);
        }
        $subscriber->confirmed_at ??= now();
        $subscriber->unsubscribed_at = null; // re-subscribe clears the flag
        $subscriber->save();

        return $subscriber;
    }

    public function unsubscribeByToken(string $token): bool
    {
        $subscriber = Subscriber::query()->where('unsubscribe_token', $token)->first();
        if ($subscriber === null) {
            return false;
        }

        $subscriber->update(['unsubscribed_at' => now()]);

        return true;
    }

    public function eachActive(Closure $callback, int $chunk = 500): void
    {
        Subscriber::query()->active()->chunkById($chunk, function ($subscribers) use ($callback): void {
            foreach ($subscribers as $subscriber) {
                $callback($subscriber);
            }
        });
    }

    public function countActive(): int
    {
        return Subscriber::query()->active()->count();
    }
}
