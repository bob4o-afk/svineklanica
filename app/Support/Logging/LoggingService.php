<?php

declare(strict_types=1);

namespace App\Support\Logging;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Operational logging goes through here — no bare `Log::` calls scattered across
 * the codebase (backend.md §8). Optionally targets a named channel.
 */
class LoggingService
{
    public function __construct(private readonly ?string $channel = null) {}

    /** @param array<string, mixed> $context */
    public function info(string $message, array $context = []): void
    {
        $this->log()->info($message, $context);
    }

    /** @param array<string, mixed> $context */
    public function warning(string $message, array $context = []): void
    {
        $this->log()->warning($message, $context);
    }

    /** @param array<string, mixed> $context */
    public function error(string $message, array $context = []): void
    {
        $this->log()->error($message, $context);
    }

    private function log(): LoggerInterface
    {
        return Log::channel($this->channel);
    }
}
