<?php

declare(strict_types=1);

namespace Modules\Presentation\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

/** Contract `RegionAggregate` — per-oblast flag counts that colour the map. */
#[MapName(SnakeCaseMapper::class)]
final class RegionAggregateData extends Data
{
    public function __construct(
        public string $regionCode,
        public string $regionName,
        public int $metric,
        public int $flagCount,
        public MoneyAmountData|Optional $totalValue,
    ) {}
}
