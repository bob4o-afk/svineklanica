<?php

declare(strict_types=1);

namespace Modules\Procurement\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Procurement\Actions\EmbedRecordsAction;

/**
 * Async embedding generation (backend.md §3) — embedding many rows calls an
 * external API, so it must never run in a web request. The CLI `search:embed`
 * runs the action inline; an admin-triggered re-index dispatches this instead.
 * Idempotent (only NULL embeddings) so it is retry-safe.
 */
final class EmbedRecordsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly string $type = 'all',
        public readonly ?int $limit = null,
    ) {}

    public function handle(EmbedRecordsAction $action): void
    {
        $action->execute($this->type, $this->limit);
    }
}
