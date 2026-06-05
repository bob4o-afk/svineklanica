<?php

declare(strict_types=1);

namespace Modules\Procurement\Console\Commands;

use Illuminate\Console\Command;
use Modules\Procurement\Actions\IngestSourceAction;
use Modules\Procurement\Jobs\IngestSourceJob;

/**
 * `php artisan ingest:run --source=ted` — reads the scraper's NDJSON and
 * idempotently upserts it (scraping.md §2). Runs inline + prints an honest
 * summary by default; `--queue` dispatches the async job instead.
 */
final class IngestRunCommand extends Command
{
    protected $signature = 'ingest:run
        {--source= : Source id, e.g. ted (reads storage/ingest/normalized/<source>.ndjson)}
        {--path= : Override the NDJSON path}
        {--queue : Dispatch the async ingest job instead of running inline}';

    protected $description = 'Ingest a source NDJSON file into the database (idempotent upsert).';

    public function handle(IngestSourceAction $action): int
    {
        $source = (string) $this->option('source');
        if ($source === '') {
            $this->error('A --source is required, e.g. --source=ted');

            return self::FAILURE;
        }

        $path = $this->option('path') !== null ? (string) $this->option('path') : null;

        if ($this->option('queue')) {
            IngestSourceJob::dispatch($source, $path);
            $this->info("Queued ingest job for source '{$source}'.");

            return self::SUCCESS;
        }

        $summary = $action->execute($source, $path);

        $this->info("Ingest '{$summary->source}' — {$summary->path}");
        $this->table(
            ['Read', 'Written', 'Skipped'],
            [[$summary->read, $summary->written, $summary->skipped]],
        );

        if ($summary->skipReasons !== []) {
            $this->warn('Skipped records:');
            foreach ($summary->skipReasons as $reason) {
                $this->line("  - {$reason}");
            }
        }

        return self::SUCCESS;
    }
}
