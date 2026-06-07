<?php

declare(strict_types=1);

namespace Modules\Presentation\Data;

use Modules\Detection\Data\SphereSpendShareData;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

/** Contract `CorruptionTaxSphere` — flagged vs total spend in one sphere + the user's slice. */
#[MapName(SnakeCaseMapper::class)]
final class CorruptionSphereData extends Data
{
    public function __construct(
        public string|Optional $sphereLabel, // Bulgarian sphere name; absent when unclassified
        public MoneyAmountData $total,
        public MoneyAmountData $flagged,
        public float $rate,                  // flagged / total within the sphere
        public MoneyAmountData $userAmount,  // the user's taxes attributed to this sphere
    ) {}

    public static function fromResult(SphereSpendShareData $s, string $currency): self
    {
        return new self(
            sphereLabel: $s->sphere?->label() ?? Optional::create(),
            total: new MoneyAmountData($s->total, $currency, true),
            flagged: new MoneyAmountData($s->flagged, $currency, true),
            rate: $s->rate,
            userAmount: new MoneyAmountData($s->userCorruptionAmount, $currency, true),
        );
    }
}
