<?php

declare(strict_types=1);

namespace Modules\Detection\Enums;

use App\Shared\Contracts\HasLabel;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Editorial workflow for a flag-post (API_SEAM.md). Block 3100. */
#[TypeScript]
enum ApprovalStatus: int implements HasLabel
{
    case Detected = 3100;
    case Pending = 3110;
    case Approved = 3120;
    case Rejected = 3130;

    public function label(): string
    {
        return match ($this) {
            self::Detected => __('enums.approval_status.detected'),
            self::Pending => __('enums.approval_status.pending'),
            self::Approved => __('enums.approval_status.approved'),
            self::Rejected => __('enums.approval_status.rejected'),
        };
    }

    /** Wire contract.ts ApprovalStatus strings. */
    public function wire(): string
    {
        return match ($this) {
            self::Detected => 'detected',
            self::Pending => 'pending',
            self::Approved => 'approved',
            self::Rejected => 'rejected',
        };
    }

    public static function fromWire(string $value): ?self
    {
        return match ($value) {
            'detected' => self::Detected,
            'pending' => self::Pending,
            'approved' => self::Approved,
            'rejected' => self::Rejected,
            default => null,
        };
    }
}
