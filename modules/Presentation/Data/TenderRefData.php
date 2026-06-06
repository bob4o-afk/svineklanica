<?php

declare(strict_types=1);

namespace Modules\Presentation\Data;

use Modules\Procurement\Models\Tender;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

/** Contract `TenderRef` — a procurement notice reference. */
#[MapName(SnakeCaseMapper::class)]
final class TenderRefData extends Data
{
    public function __construct(
        public string $publicId,
        public string $title,
        public string|Optional $tedId,
        public string|Optional $cpvCode,
    ) {}

    public static function fromModel(Tender $tender): self
    {
        // The TED notice id is the natural_key for TED-sourced notices.
        $tedId = $tender->source === 'ted' ? $tender->natural_key : null;

        return new self(
            publicId: $tender->public_id,
            title: $tender->title,
            tedId: $tedId ?? Optional::create(),
            cpvCode: $tender->cpv_code ?? Optional::create(),
        );
    }
}
