<?php

declare(strict_types=1);

namespace Modules\Presentation\Data;

use Spatie\LaravelData\Data;

/** Contract `PlatformStats` — the headline counters on the home hero (real, not hardcoded). */
final class PlatformStatsData extends Data
{
    public function __construct(
        public int $tenders,
        public int $flags,
        public int $detectors,
    ) {}
}
