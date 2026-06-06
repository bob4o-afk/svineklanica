<?php

declare(strict_types=1);

namespace Modules\Presentation\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/** Contract `AuthorityDetail` — a contracting authority's profile + its flag history. */
#[MapName(SnakeCaseMapper::class)]
final class AuthorityDetailData extends Data
{
    public function __construct(
        public AuthorityRefData $authority,
        public EntityStatsData $stats,
        public PaginatedFlagPostData $flags,
    ) {}
}
