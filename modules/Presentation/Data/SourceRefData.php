<?php

declare(strict_types=1);

namespace Modules\Presentation\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/** Contract `SourceRef` — the primary-source link behind a claim (no source → no flag). */
#[MapName(SnakeCaseMapper::class)]
final class SourceRefData extends Data
{
    public function __construct(
        public string $url,
        public string $label,
        public string $fetchedAt,
    ) {}
}
