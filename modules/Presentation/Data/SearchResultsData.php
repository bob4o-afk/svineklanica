<?php

declare(strict_types=1);

namespace Modules\Presentation\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/** Contract `SearchResults` — global search grouped by entity type. */
#[MapName(SnakeCaseMapper::class)]
final class SearchResultsData extends Data
{
    /**
     * @param  AuthorityRefData[]  $authorities
     * @param  CompanyRefData[]  $companies
     * @param  TenderRefData[]  $tenders
     */
    public function __construct(
        public array $authorities,
        public array $companies,
        public array $tenders,
    ) {}
}
