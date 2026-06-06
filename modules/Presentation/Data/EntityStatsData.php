<?php

declare(strict_types=1);

namespace Modules\Presentation\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

/** Contract `EntityStats` — headline counters on an authority/company profile. */
#[MapName(SnakeCaseMapper::class)]
final class EntityStatsData extends Data
{
    public function __construct(
        public int $flagCount,
        public int $criticalCount,
        public MoneyAmountData|Optional $totalValue,
    ) {}
}
