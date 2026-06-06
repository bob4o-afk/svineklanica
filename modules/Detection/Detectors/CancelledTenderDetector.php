<?php

declare(strict_types=1);

namespace Modules\Detection\Detectors;

use App\Shared\Enums\FlagSeverity;
use Modules\Detection\Enums\FlagType;

/**
 * 🚪 Announced-then-cancelled (CLAUDE.md §1.1.4). Flags tenders the contracting
 * authority opened then cancelled or terminated — a hard termination scores louder
 * than a plain cancellation. Subject is the tender.
 */
final class CancelledTenderDetector extends AbstractDetector
{
    private const SCORE_TERMINATED = 70;

    private const SCORE_CANCELLED = 50;

    public function type(): FlagType
    {
        return FlagType::Cancelled;
    }

    protected function detect(): array
    {
        $rows = [];
        foreach ($this->procurement->cancelledTenders() as $tender) {
            $score = $tender->wasTerminated ? self::SCORE_TERMINATED : self::SCORE_CANCELLED;

            $rows[] = [
                'type' => FlagType::Cancelled,
                'sphere' => $tender->sphere,
                'category' => $tender->category,
                'score' => $score,
                'severity' => FlagSeverity::fromScore($score),
                'subject_type' => 'tender',
                'subject_id' => $tender->tenderId,
                'subject_label' => $tender->label,
                'explanation_bg' => __('detection.cancelled', ['status' => $tender->statusLabel]),
                'source_urls' => [$tender->sourceUrl],
                'evidence' => [
                    'status' => $tender->statusLabel,
                    'terminated' => $tender->wasTerminated,
                    'cancelled_at' => $tender->cancelledAt,
                ],
                'detected_at' => now(),
            ];
        }

        return $rows;
    }
}
