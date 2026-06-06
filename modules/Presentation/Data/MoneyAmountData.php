<?php

declare(strict_types=1);

namespace Modules\Presentation\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/** Contract `MoneyAmount` — currency-normalized so a citizen can compare prices. */
#[MapName(SnakeCaseMapper::class)]
final class MoneyAmountData extends Data
{
    public function __construct(
        public float $amount,
        public string $currency,
        public bool $vatIncluded,
    ) {}
}
