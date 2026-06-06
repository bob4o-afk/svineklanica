<?php

declare(strict_types=1);

namespace Modules\Presentation\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

/** Contract `EvidenceItem` — one labelled number/fact behind a flag (the receipts). */
#[MapName(SnakeCaseMapper::class)]
final class EvidenceItemData extends Data
{
    public function __construct(
        public string $label,
        public string|int|float $value,
        public MoneyAmountData|Optional $money,
    ) {}

    /** @param  array<string, mixed>  $row */
    public static function fromArray(array $row): self
    {
        $money = Optional::create();
        if (isset($row['money']) && is_array($row['money'])) {
            $money = new MoneyAmountData(
                amount: (float) ($row['money']['amount'] ?? 0),
                currency: (string) ($row['money']['currency'] ?? 'BGN'),
                vatIncluded: (bool) ($row['money']['vat_included'] ?? ($row['money']['vatIncluded'] ?? true)),
            );
        }

        /** @var string|int|float $value */
        $value = $row['value'] ?? '';

        return new self(
            label: (string) ($row['label'] ?? ''),
            value: $value,
            money: $money,
        );
    }
}
