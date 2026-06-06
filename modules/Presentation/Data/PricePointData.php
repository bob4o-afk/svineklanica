<?php

declare(strict_types=1);

namespace Modules\Presentation\Data;

use Modules\Procurement\Models\PriceSnapshot;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

/** Contract `PricePoint` — one point-in-time price capture on the price-over-time graph. */
#[MapName(SnakeCaseMapper::class)]
final class PricePointData extends Data
{
    public function __construct(
        public string $capturedAt,
        public MoneyAmountData $price,
        public SourceRefData $source,
        public TenderRefData|Optional $tenderRef,
        public CompanyRefData|Optional $vendor,
    ) {}

    public static function fromModel(PriceSnapshot $snapshot): self
    {
        $tender = $snapshot->tenderItem?->tender;
        $vendor = $tender?->winner;
        $capturedAt = $snapshot->captured_at?->toIso8601String() ?? '';

        return new self(
            capturedAt: $capturedAt,
            price: new MoneyAmountData(
                amount: (float) $snapshot->price,
                currency: (string) $snapshot->currency,
                vatIncluded: true,
            ),
            source: new SourceRefData(
                url: (string) $snapshot->source_url,
                label: 'Първоизточник',
                fetchedAt: $capturedAt,
            ),
            tenderRef: $tender !== null ? TenderRefData::fromModel($tender) : Optional::create(),
            vendor: $vendor !== null ? CompanyRefData::fromModel($vendor) : Optional::create(),
        );
    }
}
