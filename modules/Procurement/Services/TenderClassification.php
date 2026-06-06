<?php

declare(strict_types=1);

namespace Modules\Procurement\Services;

use App\Shared\Enums\CorruptionCategory;
use App\Shared\Enums\Sphere;

/** Result of {@see SphereClassifier::classify()} — the sphere (maybe unknown) + category. */
final readonly class TenderClassification
{
    public function __construct(
        public ?Sphere $sphere,
        public CorruptionCategory $category,
    ) {}
}
