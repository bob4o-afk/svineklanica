<?php

declare(strict_types=1);

namespace Modules\Procurement\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Procurement\Actions\IngestSourceAction;

/**
 * Async ingest (backend.md §3) — for a web/admin-triggered run, so the request
 * returns fast. The CLI `ingest:run` runs the Action inline by default (a CLI is
 * already out-of-band) and only uses this when `--queue` is passed.
 */
final class IngestSourceJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public readonly string $source,
        public readonly ?string $path = null,
    ) {}

    public function handle(IngestSourceAction $action): void
    {
        $action->execute($this->source, $this->path);
    }
}
