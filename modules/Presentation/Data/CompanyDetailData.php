<?php

declare(strict_types=1);

namespace Modules\Presentation\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/** Contract `CompanyDetail` — a company's profile, flag history + related (shell) companies. */
#[MapName(SnakeCaseMapper::class)]
final class CompanyDetailData extends Data
{
    /**
     * @param  CompanyRefData[]  $related
     */
    public function __construct(
        public CompanyRefData $company,
        public EntityStatsData $stats,
        public array $related,
        public PaginatedFlagPostData $flags,
    ) {}
}
