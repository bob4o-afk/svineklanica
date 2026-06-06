<?php

declare(strict_types=1);

namespace Modules\Notifications\Contracts;

use Closure;
use Modules\Notifications\Models\Subscriber;

/** The only place that touches the subscribers table (backend.md §2). */
interface SubscriberRepository
{
    /** Subscribe (or re-activate an unsubscribed) e-mail. Idempotent on e-mail. */
    public function subscribe(string $email): Subscriber;

    /** Mark the holder of this token as unsubscribed. Returns false if unknown. */
    public function unsubscribeByToken(string $token): bool;

    /**
     * Stream active subscribers in chunks to the callback — never loads them all
     * into memory (the list can grow large).
     *
     * @param  Closure(Subscriber): void  $callback
     */
    public function eachActive(Closure $callback, int $chunk = 500): void;

    public function countActive(): int;
}
