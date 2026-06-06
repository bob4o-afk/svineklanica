<?php

declare(strict_types=1);

namespace Modules\Presentation\Data;

use Modules\Procurement\Models\ContractingAuthority;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/** Contract `AuthorityRef` — a contracting authority (възложител) reference. */
#[MapName(SnakeCaseMapper::class)]
final class AuthorityRefData extends Data
{
    public function __construct(
        public string $publicId,
        public string $name,
        public ?string $regionCode,
    ) {}

    public static function fromModel(ContractingAuthority $authority): self
    {
        return new self(
            publicId: $authority->public_id,
            name: $authority->name,
            regionCode: $authority->region,
        );
    }
}
