<?php

declare(strict_types=1);

namespace Modules\Detection\Console\Commands;

use Illuminate\Console\Command;
use Modules\Detection\Detectors\Contracts\Detector;
use Modules\Detection\Detectors\DetectorRegistry;
use Modules\Detection\Enums\FlagType;
use Modules\Detection\Jobs\RunDetectorJob;

/**
 * `php artisan detect:run` — recompute red-flag detectors over the ingested data
 * (CLAUDE.md §1.1). Runs inline + prints a summary by default; `--queue` dispatches
 * the async job per detector. `--detector=<slug>` runs just one.
 */
final class DetectRunCommand extends Command
{
    protected $signature = 'detect:run
        {--detector= : Run one detector by slug: price-discrepancy | serial-winner | cancelled}
        {--queue : Dispatch the async detector job(s) instead of running inline}';

    protected $description = 'Run the red-flag detectors over the ingested data (idempotent recompute).';

    /** Friendly CLI slugs → FlagType. */
    private const SLUGS = [
        'price-discrepancy' => FlagType::PriceDiscrepancy,
        'serial-winner' => FlagType::SerialWinner,
        'cancelled' => FlagType::Cancelled,
    ];

    public function handle(DetectorRegistry $registry): int
    {
        $slug = $this->option('detector');
        if ($slug !== null && ! isset(self::SLUGS[$slug])) {
            $this->error("Unknown --detector '{$slug}'. Valid: ".implode(', ', array_keys(self::SLUGS)));

            return self::FAILURE;
        }

        /** @var array<int, Detector> $detectors */
        $detectors = $slug !== null
            ? array_filter([$registry->get(self::SLUGS[$slug])])
            : $registry->all();

        if ($this->option('queue')) {
            foreach ($detectors as $detector) {
                RunDetectorJob::dispatch($detector->type());
            }
            $this->info('Queued '.count($detectors).' detector job(s).');

            return self::SUCCESS;
        }

        $summary = [];
        foreach ($detectors as $detector) {
            $summary[] = [$detector->type()->label(), $detector->run()];
        }

        $this->table(['Detector', 'Flags written'], $summary);

        return self::SUCCESS;
    }
}
