<?php

declare(strict_types=1);

namespace Modules\Procurement\Enums;

use App\Shared\Contracts\HasLabel;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Lifecycle of a tender — drives the "announced-then-cancelled" detector
 * (CLAUDE.md §1.1.4). Block 1000.
 */
#[TypeScript]
enum TenderStatus: int implements HasLabel
{
    case Announced = 1000;
    case Open = 1010;
    case Awarded = 1020;
    case Cancelled = 1030;
    case Terminated = 1040;

    public function label(): string
    {
        return match ($this) {
            self::Announced => __('enums.tender_status.announced'),
            self::Open => __('enums.tender_status.open'),
            self::Awarded => __('enums.tender_status.awarded'),
            self::Cancelled => __('enums.tender_status.cancelled'),
            self::Terminated => __('enums.tender_status.terminated'),
        };
    }
}
