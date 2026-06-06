<?php

declare(strict_types=1);

namespace Modules\Detection\Detectors;

use Modules\Detection\Detectors\Contracts\Detector;
use Modules\Detection\Enums\FlagType;

/**
 * The catalogue of available detectors, keyed by their {@see FlagType}. Lets the
 * job/command run one detector or all of them without hard-coding the list.
 */
final class DetectorRegistry
{
    /** @var array<int, Detector> indexed by FlagType value */
    private array $byType = [];

    public function __construct(
        PriceDiscrepancyDetector $priceDiscrepancy,
        SerialWinnerDetector $serialWinner,
        CancelledTenderDetector $cancelled,
    ) {
        foreach ([$priceDiscrepancy, $serialWinner, $cancelled] as $detector) {
            $this->byType[$detector->type()->value] = $detector;
        }
    }

    /** @return array<int, Detector> */
    public function all(): array
    {
        return array_values($this->byType);
    }

    public function get(FlagType $type): ?Detector
    {
        return $this->byType[$type->value] ?? null;
    }
}
