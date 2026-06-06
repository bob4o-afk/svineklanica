<?php

declare(strict_types=1);

namespace Modules\Detection\Console\Commands;

use Illuminate\Console\Command;
use Modules\Detection\Actions\IngestVerdictsAction;

/**
 * `php artisan analyze:ingest --source=ted` — reads the AI analyzer's verdict NDJSON
 * (apps/ai writes storage/ingest/verdicts/<source>.ndjson) and persists each verdict
 * as a Flag on its tender, so the AI's corruption assessment shows up in the citizen
 * feed/map. Idempotent (AI-origin flags are replaced, detector flags untouched).
 */
final class AnalyzeIngestCommand extends Command
{
    protected $signature = 'analyze:ingest
        {--source= : Source id, e.g. ted (reads storage/ingest/verdicts/<source>.ndjson)}
        {--path= : Override the verdicts NDJSON path}
        {--min-score=0 : Only persist verdicts at/above this 0–100 score}';

    protected $description = 'Ingest AI verdict NDJSON into the flags table (idempotent).';

    public function handle(IngestVerdictsAction $action): int
    {
        $source = (string) $this->option('source');
        if ($source === '') {
            $this->error('A --source is required, e.g. --source=ted');

            return self::FAILURE;
        }

        $path = $this->option('path') !== null ? (string) $this->option('path') : null;
        $minScore = (int) $this->option('min-score');

        $summary = $action->execute($source, $path, $minScore);

        $this->info("AI verdict ingest '{$summary['source']}' — {$summary['path']}");
        $this->table(
            ['Read', 'Written (flags)', 'Skipped'],
            [[$summary['read'], $summary['written'], $summary['skipped']]],
        );

        if ($summary['levels'] !== []) {
            $this->line('By level:');
            foreach ($summary['levels'] as $level => $count) {
                $this->line("  - {$level}: {$count}");
            }
        }

        if ($summary['skipReasons'] !== []) {
            $this->warn('Skipped:');
            foreach ($summary['skipReasons'] as $reason) {
                $this->line("  - {$reason}");
            }
        }

        return self::SUCCESS;
    }
}
