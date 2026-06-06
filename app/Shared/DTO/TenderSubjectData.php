<?php

declare(strict_types=1);

namespace App\Shared\DTO;

use App\Shared\Enums\CorruptionCategory;
use App\Shared\Enums\Sphere;

/**
 * A minimal handle on an ingested tender, resolved by (source, natural_key), so the
 * Detection module can attach an AI verdict's Flag to the right tender subject
 * without importing Procurement's Eloquent model (backend.md §1). `cpv` drives the
 * citizen-facing sector; `region` is the raw place string for the map.
 */
final readonly class TenderSubjectData
{
    public function __construct(
        public int $tenderId,
        public string $label,
        public string $sourceUrl,
        public ?string $cpv,
        public ?string $region,
        public ?Sphere $sphere,
        public ?CorruptionCategory $category,
    ) {}
}
