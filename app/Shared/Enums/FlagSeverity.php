<?php

declare(strict_types=1);

namespace App\Shared\Enums;

use App\Shared\Contracts\HasLabel;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The severity BAND shown for a flag or a post (🟢/🟡/🔴, CLAUDE.md §1.0/§1.2).
 * Block 3000. One of the three core cross-cutting dimensions (Sphere → Category →
 * Severity), so it lives in the shared kernel — used by Detection (Flag) and
 * Publishing (Post) without a cross-module import (backend.md §1).
 *
 * The band is DERIVED from the stored 0–100 suspicion `score`: detectors compute
 * the score, {@see self::fromScore()} maps it to a band via each case's own
 * {@see self::minScore()}, and the citizen sees the band (+ the %).
 */
#[TypeScript]
enum FlagSeverity: int implements HasLabel
{
    case Low = 3000;
    case Medium = 3010;
    case High = 3020;
    case Critical = 3030;

    /** The inclusive lower bound of this band on the 0–100 score scale. */
    public function minScore(): int
    {
        return match ($this) {
            self::Low => 0,
            self::Medium => 40,
            self::High => 70,
            self::Critical => 90,
        };
    }

    /** The emoji dot the feed/map use for this band (🟢/🟡/🔴). */
    public function dot(): string
    {
        return match ($this) {
            self::Low => '🟢',
            self::Medium => '🟡',
            self::High, self::Critical => '🔴',
        };
    }

    /** Map a 0–100 suspicion score to its band (cut-offs come from each case's minScore). */
    public static function fromScore(int $score): self
    {
        $clamped = max(0, min(100, $score));

        // Highest-first: the first band whose floor the score clears wins.
        foreach ([self::Critical, self::High, self::Medium, self::Low] as $band) {
            if ($clamped >= $band->minScore()) {
                return $band;
            }
        }

        return self::Low;
    }

    public function label(): string
    {
        return match ($this) {
            self::Low => __('enums.flag_severity.low'),
            self::Medium => __('enums.flag_severity.medium'),
            self::High => __('enums.flag_severity.high'),
            self::Critical => __('enums.flag_severity.critical'),
        };
    }
}
