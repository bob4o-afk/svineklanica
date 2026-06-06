<?php

declare(strict_types=1);

namespace Modules\Detection\Jobs;

use App\Support\Logging\LoggingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Detection\Detectors\DetectorRegistry;
use Modules\Detection\Enums\FlagType;

/**
 * Runs one detector over the full dataset (backend.md §3 — heavy work is async,
 * §11 — detectors run as queued jobs). Idempotent + retry-safe: the detector
 * replaces its type's flags atomically, so a retry can't duplicate.
 */
final class RunDetectorJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 15;

    public function __construct(public readonly FlagType $type) {}

    public function handle(DetectorRegistry $registry, LoggingService $log): void
    {
        $detector = $registry->get($this->type);
        if ($detector === null) {
            $log->warning('detect: no detector for type', ['type' => $this->type->value]);

            return;
        }

        $written = $detector->run();

        $log->info('detect: detector run complete', [
            'type' => $this->type->value,
            'flags_written' => $written,
        ]);
    }
}
