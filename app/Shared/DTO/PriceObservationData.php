<?php

declare(strict_types=1);

namespace App\Shared\DTO;

use App\Shared\Enums\CorruptionCategory;
use App\Shared\Enums\Sphere;

/**
 * One priced line-item observation, read from a price snapshot. Crosses the
 * Procurement → Detection seam (backend.md §1) so the price-discrepancy detector
 * can cluster by product and spot outliers without importing procurement models.
 */
final readonly class PriceObservationData
{
    public function __construct(
        public int $tenderId,
        public string $tenderLabel,
        public string $sourceUrl,
        public string $productKey,
        public string $description,
        public float $price,
        public string $currency,
        public ?Sphere $sphere,
        public ?CorruptionCategory $category,
    ) {}
}
