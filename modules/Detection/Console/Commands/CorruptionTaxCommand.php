<?php

declare(strict_types=1);

namespace Modules\Detection\Console\Commands;

use Illuminate\Console\Command;
use Modules\Detection\Services\CorruptionTaxCalculator;

/**
 * `php artisan calc:corruption-tax --taxes=1200` — the corruption-tax calculator
 * as a script (CLAUDE.md). Computes live from ingested data: what share of public
 * spend carries a red flag, and how much of the citizen's taxes that works out to.
 */
final class CorruptionTaxCommand extends Command
{
    protected $signature = 'calc:corruption-tax {--taxes=0 : Taxes the citizen paid, in BGN}';

    protected $description = 'Compute how much of a citizen\'s taxes funded flagged deals (live).';

    public function handle(CorruptionTaxCalculator $calculator): int
    {
        $taxes = (float) $this->option('taxes');
        $result = $calculator->calculate($taxes);

        $this->info(sprintf(
            'Flagged %s of %s %s total public spend → %.2f%% corruption rate.',
            number_format($result->flaggedSpend, 2),
            number_format($result->totalSpend, 2),
            $result->currency,
            $result->corruptionRate * 100,
        ));
        $this->line(sprintf(
            'Of your %s %s in taxes, %s %s funded flagged deals.',
            number_format($result->taxesPaid, 2),
            $result->currency,
            number_format($result->userCorruptionAmount, 2),
            $result->currency,
        ));

        if ($result->topCases !== []) {
            $this->newLine();
            $this->line('Where it went (top flagged cases):');
            $this->table(
                ['Kind', 'Title', 'Amount', 'Score', 'Your share', 'Source'],
                array_map(static fn ($c): array => [
                    $c->kind,
                    mb_strimwidth($c->title, 0, 44, '…'),
                    number_format($c->amount, 2).' '.$c->currency,
                    $c->score.'%',
                    number_format($c->userShare, 2).' '.$result->currency,
                    $c->sourceUrl,
                ], $result->topCases),
            );
        }

        return self::SUCCESS;
    }
}
