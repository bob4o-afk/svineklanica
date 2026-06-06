<?php

declare(strict_types=1);

namespace Modules\Presentation\Data;

use Modules\Procurement\Models\Company;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/** Contract `CompanyRef` — a contractor, unified on EIK (БУЛСТАТ). */
#[MapName(SnakeCaseMapper::class)]
final class CompanyRefData extends Data
{
    public function __construct(
        public string $publicId,
        public string $eik,
        public string $name,
    ) {}

    public static function fromModel(Company $company): self
    {
        return new self(
            publicId: $company->public_id,
            eik: (string) $company->eik,
            name: $company->name,
        );
    }
}
