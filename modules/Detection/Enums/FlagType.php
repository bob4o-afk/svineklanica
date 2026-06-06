<?php

declare(strict_types=1);

namespace Modules\Detection\Enums;

use App\Shared\Contracts\HasLabel;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The seven red-flag detectors (CLAUDE.md §1.1). One type per Flag. Block 2000. */
#[TypeScript]
enum FlagType: int implements HasLabel
{
    case PriceDiscrepancy = 2000;
    case TailoredSpec = 2010;
    case SerialWinner = 2020;
    case Cancelled = 2030;
    case ImplausibleScope = 2040;
    case DelayedPayment = 2050;
    case DocClone = 2060;

    public function label(): string
    {
        return match ($this) {
            self::PriceDiscrepancy => __('enums.flag_type.price_discrepancy'),
            self::TailoredSpec => __('enums.flag_type.tailored_spec'),
            self::SerialWinner => __('enums.flag_type.serial_winner'),
            self::Cancelled => __('enums.flag_type.cancelled'),
            self::ImplausibleScope => __('enums.flag_type.implausible_scope'),
            self::DelayedPayment => __('enums.flag_type.delayed_payment'),
            self::DocClone => __('enums.flag_type.doc_clone'),
        };
    }

    /** contract.ts FlagType wire value. */
    public function wire(): string
    {
        return match ($this) {
            self::PriceDiscrepancy => 'price_discrepancy',
            self::TailoredSpec => 'tailored_spec',
            self::SerialWinner => 'serial_winner',
            self::Cancelled => 'cancelled',
            self::ImplausibleScope => 'implausible_scope',
            self::DelayedPayment => 'delayed_payment',
            self::DocClone => 'doc_clone',
        };
    }
}
