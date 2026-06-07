<?php

declare(strict_types=1);

namespace Modules\Procurement\Console\Commands;

use Illuminate\Console\Command;
use Modules\Procurement\Actions\EmbedRecordsAction;
use Modules\Procurement\Jobs\EmbedRecordsJob;

/**
 * `php artisan search:embed --type=all` — vectorize the DB by embedding each
 * entity's searchable text with Google (CLAUDE.md §3). Idempotent: only rows with
 * a NULL embedding are processed. Runs inline + prints an honest summary by
 * default; `--queue` dispatches the async job instead.
 */
final class EmbedRunCommand extends Command
{
    protected $signature = 'search:embed
        {--type=all : What to embed: tenders|companies|authorities|all}
        {--limit= : Cap the number of rows per type (for a quick demo run)}
        {--queue : Dispatch the async embedding job instead of running inline}';

    protected $description = 'Generate + store Google embeddings for the search index (idempotent).';

    public function handle(EmbedRecordsAction $action): int
    {
        $type = (string) $this->option('type');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        if ($this->option('queue')) {
            EmbedRecordsJob::dispatch($type, $limit);
            $this->info("Queued embedding job (type '{$type}').");

            return self::SUCCESS;
        }

        $summary = $action->execute($type, $limit);

        $this->info("Embed '{$summary->type}'");
        $this->table(['Embedded', 'Skipped'], [[$summary->embedded, $summary->skipped]]);

        if ($summary->skipReasons !== []) {
            $this->warn('Skipped:');
            foreach ($summary->skipReasons as $reason) {
                $this->line("  - {$reason}");
            }
        }

        return self::SUCCESS;
    }
}
