<?php

declare(strict_types=1);

namespace Modules\Presentation\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

/** Contract `PriceSeries` — a product's price across snapshots (the "watch it creep" graph). */
#[MapName(SnakeCaseMapper::class)]
final class PriceSeriesData extends Data
{
    /** @param  PricePointData[]  $points */
    public function __construct(
        public string $seriesKey,
        public string $productLabel,
        public string|Optional $cpvCode,
        public string|Optional $unit,
        public array $points,
    ) {}
}
