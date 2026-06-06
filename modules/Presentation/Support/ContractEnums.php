<?php

declare(strict_types=1);

namespace Modules\Presentation\Support;

use App\Shared\Enums\FlagSeverity;
use Modules\Detection\Enums\FlagType;

/**
 * Translates the backend's int-backed domain enums (backend.md §9.5) into the
 * string literals the citizen-facing API contract uses (apps/web `contract.ts`).
 *
 * The read/presentation seam owns this mapping: the wire stays human-readable
 * snake-case strings, the storage stays compact ints.
 */
final class ContractEnums
{
    /** Domain FlagType → contract `FlagType` string. */
    public static function flagType(FlagType $type): string
    {
        return match ($type) {
            FlagType::PriceDiscrepancy => 'price_discrepancy',
            FlagType::TailoredSpec => 'tailored_spec',
            FlagType::SerialWinner => 'serial_winner',
            FlagType::Cancelled => 'cancelled',
            FlagType::ImplausibleScope => 'implausible_scope',
            FlagType::DelayedPayment => 'delayed_payment',
            FlagType::DocClone => 'doc_clone',
        };
    }

    /** Contract `FlagType` string → domain FlagType (feed filter parsing); null if unknown. */
    public static function flagTypeFrom(string $value): ?FlagType
    {
        return match ($value) {
            'price_discrepancy' => FlagType::PriceDiscrepancy,
            'tailored_spec' => FlagType::TailoredSpec,
            'serial_winner' => FlagType::SerialWinner,
            'cancelled' => FlagType::Cancelled,
            'implausible_scope' => FlagType::ImplausibleScope,
            'delayed_payment' => FlagType::DelayedPayment,
            'doc_clone' => FlagType::DocClone,
            default => null,
        };
    }

    /** Domain FlagSeverity band → contract `FlagSeverity` string. */
    public static function severity(FlagSeverity $severity): string
    {
        return match ($severity) {
            FlagSeverity::Low => 'low',
            FlagSeverity::Medium => 'medium',
            FlagSeverity::High => 'high',
            FlagSeverity::Critical => 'critical',
        };
    }

    /** Contract `FlagSeverity` string → domain band (feed filter parsing); null if unknown. */
    public static function severityFrom(string $value): ?FlagSeverity
    {
        return match ($value) {
            'low' => FlagSeverity::Low,
            'medium' => FlagSeverity::Medium,
            'high' => FlagSeverity::High,
            'critical' => FlagSeverity::Critical,
            default => null,
        };
    }
}
