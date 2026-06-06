<?php

declare(strict_types=1);

namespace App\Shared\DTO;

use App\Shared\Enums\CorruptionCategory;
use App\Shared\Enums\Sphere;

/**
 * One flagged spend item (a flagged tender or payment) with its amount + source,
 * for the corruption-tax calculator's "where your money went" breakdown. Crosses
 * the Procurement → consumer seam (backend.md §1). Every case keeps its source_url.
 */
final readonly class FlaggedCaseData
{
    public function __construct(
        public string $kind,        // 'tender' | 'payment'
        public string $title,
        public float $amount,       // full contract value (the headline number)
        public string $currency,
        public string $sourceUrl,   // always set — no source → not shown
        public ?Sphere $sphere,
        public ?CorruptionCategory $category,
        public float $weight,       // suspicion weight ∈ [0,1] — how much of `amount` counts as flagged
    ) {}
}
