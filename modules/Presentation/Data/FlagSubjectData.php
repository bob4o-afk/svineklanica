<?php

declare(strict_types=1);

namespace Modules\Presentation\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

/** Contract `FlagSubject` — what the flag is about: a tender, authority, or company. */
#[MapName(SnakeCaseMapper::class)]
final class FlagSubjectData extends Data
{
    public function __construct(
        public string $type,
        public AuthorityRefData|Optional $authority,
        public CompanyRefData|Optional $company,
        public TenderRefData|Optional $tender,
    ) {}
}
