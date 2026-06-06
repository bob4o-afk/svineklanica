<?php

declare(strict_types=1);

namespace Modules\Identity\Listeners;

use App\Support\Logging\LoggingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Identity\Events\HoneypotEvent;

/**
 * Logs a honeypot hit to the `security` channel (security.md §10–§11). Queued
 * (backend.md §3) — logging/monitoring is a side effect, it must never block the
 * (fake) response we hand the attacker.
 */
final class LogHoneypotHitListener implements ShouldQueue
{
    public function handle(HoneypotEvent $event): void
    {
        (new LoggingService('security'))->warning('honeypot.hit', [
            'route' => $event->route,
            'occurred_at' => $event->occurredAt,
            'fingerprint' => $event->fingerprint,
        ]);
    }
}
