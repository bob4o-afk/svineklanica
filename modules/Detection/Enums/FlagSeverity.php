<?php

declare(strict_types=1);

namespace Modules\Detection\Enums;

use App\Shared\Contracts\HasLabel;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** How loud a flag should be in the feed (CLAUDE.md §1.2). Block 3000. */
#[TypeScript]
enum FlagSeverity: int implements HasLabel
{
    case Low = 3000;
    case Medium = 3010;
    case High = 3020;
    case Critical = 3030;

    public function label(): string
    {
        return match ($this) {
            self::Low => __('enums.flag_severity.low'),
            self::Medium => __('enums.flag_severity.medium'),
            self::High => __('enums.flag_severity.high'),
            self::Critical => __('enums.flag_severity.critical'),
        };
    }

    /** contract.ts FlagSeverity wire value. */
    public function wire(): string
    {
        return match ($this) {
            self::Low => 'low',
            self::Medium => 'medium',
            self::High => 'high',
            self::Critical => 'critical',
        };
    }
}
