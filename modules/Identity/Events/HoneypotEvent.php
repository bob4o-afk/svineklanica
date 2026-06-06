<?php

declare(strict_types=1);

namespace Modules\Identity\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when a caller trips a honeypot decoy route (security.md §10). Carries the
 * caller fingerprint so the queued listener can log the full interaction to the
 * `security` channel for study. Defensive only — we observe, we never hack back.
 */
final class HoneypotEvent
{
    use Dispatchable;

    /** @param array<string, mixed> $fingerprint */
    public function __construct(
        public readonly array $fingerprint,
        public readonly string $route,
        public readonly string $occurredAt,
    ) {}
}
