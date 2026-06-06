<?php

declare(strict_types=1);

namespace Modules\Detection\Data;

use App\Shared\Enums\Sphere;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Per-sphere slice of the corruption-tax result: flagged vs total + the user's share there. */
#[TypeScript]
final class SphereSpendShareData extends Data
{
    public function __construct(
        public ?Sphere $sphere,
        public float $total,
        public float $flagged,
        public float $rate,                  // flagged / total within the sphere
        public float $userCorruptionAmount,  // the user's taxes attributed to this sphere's flagged spend
    ) {}
}
