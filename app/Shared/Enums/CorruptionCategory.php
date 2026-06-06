<?php

declare(strict_types=1);

namespace App\Shared\Enums;

use App\Shared\Contracts\HasLabel;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Corruption category (категория корупция) — the MECHANISM of abuse inside a
 * sphere (CLAUDE.md §1.0). For the demo exactly two; more (e.g. `конкурси за
 * работа`, §1.4) slot in later thanks to the +10 gaps.
 *
 * Shared kernel enum — used by Detection (Flag) and Procurement (ingest tagging)
 * without a cross-module import. Block 5000, cases step by 10 (backend.md §9.5).
 */
#[TypeScript]
enum CorruptionCategory: int implements HasLabel
{
    case PublicProcurement = 5000;   // обществена поръчка
    case UnregulatedPayment = 5010;  // нерегламентирани плащания

    public function label(): string
    {
        return match ($this) {
            self::PublicProcurement => __('enums.corruption_category.public_procurement'),
            self::UnregulatedPayment => __('enums.corruption_category.unregulated_payment'),
        };
    }
}
