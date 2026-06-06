<?php

declare(strict_types=1);

namespace App\Shared\DTO;

use App\Shared\Enums\CorruptionCategory;
use App\Shared\Enums\Sphere;

/**
 * A tender that was cancelled or terminated by the contracting authority. Crosses
 * the Procurement → Detection seam (backend.md §1) for the announced-then-cancelled
 * detector (CLAUDE.md §1.1.4). `wasTerminated` separates a hard termination (louder
 * signal) from a plain cancellation; `statusLabel` is the Bulgarian display string.
 */
final readonly class CancelledTenderData
{
    public function __construct(
        public int $tenderId,
        public string $label,
        public string $sourceUrl,
        public ?Sphere $sphere,
        public ?CorruptionCategory $category,
        public bool $wasTerminated,
        public string $statusLabel,
        public ?string $cancelledAt,
    ) {}
}
