<?php

declare(strict_types=1);

namespace Modules\Detection\Detectors;

use App\Shared\DTO\PriceObservationData;
use App\Shared\Enums\FlagSeverity;
use Modules\Detection\Enums\FlagType;

/**
 * 💸 Price discrepancy / overpricing (CLAUDE.md §1.1.1). Clusters priced line-items
 * by normalized product, then flags any observation priced well above the cluster
 * median — the "laptop at 10 here, 100 there" tell. Each flag links to the snapshot's
 * source and shows the spread.
 */
final class PriceDiscrepancyDetector extends AbstractDetector
{
    /** A cluster needs at least this many observations for a median to mean anything. */
    private const MIN_CLUSTER = 3;

    /** Flag an item priced at least this many times the cluster median. */
    private const RATIO_THRESHOLD = 1.5;

    public function type(): FlagType
    {
        return FlagType::PriceDiscrepancy;
    }

    protected function detect(): array
    {
        /** @var array<string, array<int, PriceObservationData>> $byProduct */
        $byProduct = [];
        foreach ($this->procurement->priceObservations() as $obs) {
            $byProduct[$obs->productKey][] = $obs;
        }

        $rows = [];
        foreach ($byProduct as $observations) {
            if (count($observations) < self::MIN_CLUSTER) {
                continue;
            }

            $median = $this->median(array_map(static fn (PriceObservationData $o): float => $o->price, $observations));
            if ($median <= 0.0) {
                continue;
            }

            foreach ($observations as $o) {
                $ratio = $o->price / $median;
                if ($ratio < self::RATIO_THRESHOLD) {
                    continue;
                }

                $score = (int) min(100, round(($ratio - 1) * 50)); // 1.5×→25, 2×→50, 3×→100

                $rows[] = [
                    'type' => FlagType::PriceDiscrepancy,
                    'sphere' => $o->sphere,
                    'category' => $o->category,
                    'score' => $score,
                    'severity' => FlagSeverity::fromScore($score),
                    'subject_type' => 'tender',
                    'subject_id' => $o->tenderId,
                    'subject_label' => $o->tenderLabel,
                    'explanation_bg' => __('detection.price_discrepancy', [
                        'product' => $o->description,
                        'price' => number_format($o->price, 2),
                        'currency' => $o->currency,
                        'ratio' => number_format($ratio, 1),
                        'median' => number_format($median, 2),
                    ]),
                    'source_urls' => [$o->sourceUrl],
                    'evidence' => [
                        'product_key' => $o->productKey,
                        'price' => $o->price,
                        'median' => round($median, 2),
                        'ratio' => round($ratio, 2),
                        'currency' => $o->currency,
                        'cluster_size' => count($observations),
                    ],
                    'detected_at' => now(),
                ];
            }
        }

        return $rows;
    }

    /** @param array<int, float> $values */
    private function median(array $values): float
    {
        sort($values);
        $n = count($values);
        $mid = intdiv($n, 2);

        return $n % 2 === 0 ? ($values[$mid - 1] + $values[$mid]) / 2 : $values[$mid];
    }
}
