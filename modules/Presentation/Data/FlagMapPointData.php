<?php

declare(strict_types=1);

namespace Modules\Presentation\Data;

use Modules\Detection\Models\Flag;
use Modules\Presentation\Support\ContractEnums;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A single flag reduced to what the map needs to pin it: its public id, the NUTS3 region it
 *  belongs to (denormalised `flags.region_code`), its severity band, and a title for the tooltip.
 *  Lightweight on purpose — the map fetches hundreds of these without the full flag payload. */
#[TypeScript]
#[MapName(SnakeCaseMapper::class)]
final class FlagMapPointData extends Data
{
    public function __construct(
        public string $publicId,
        public string $regionCode,
        public string $severity,
        public string $type,
        public string|Optional $title,
    ) {}

    public static function fromModel(Flag $flag): self
    {
        return new self(
            publicId: $flag->public_id,
            regionCode: (string) $flag->region_code,
            severity: ContractEnums::severity($flag->severity),
            type: ContractEnums::flagType($flag->type),
            title: $flag->title !== null && $flag->title !== '' ? $flag->title : Optional::create(),
        );
    }
}
