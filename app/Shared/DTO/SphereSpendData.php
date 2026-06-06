<?php

declare(strict_types=1);

namespace App\Shared\DTO;

use App\Shared\Enums\Sphere;

/** Total vs flagged spend within one sphere — the calculator's per-sphere slice. */
final readonly class SphereSpendData
{
    public function __construct(
        public ?Sphere $sphere,   // null = sphere could not be inferred at ingest
        public float $total,
        public float $flagged,
    ) {}
}
