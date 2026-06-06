<?php

declare(strict_types=1);

namespace App\Shared\Enums;

use App\Shared\Contracts\HasLabel;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Sphere (сфера) — the part of the state where the rot lives (CLAUDE.md §1.0).
 * Top-level filter + the dimension the map colours by. Demo focus: judiciary,
 * healthcare, police; education is the backlog sphere (§1.4).
 *
 * Shared kernel enum — used by Detection (Flag) and Procurement (ingest tagging)
 * without a cross-module import. Block 6000, cases step by 10 (backend.md §9.5).
 * (4000 is PostStatus, 5000 is CorruptionCategory — blocks must not collide.)
 */
#[TypeScript]
enum Sphere: int implements HasLabel
{
    case Judiciary = 6000;    // съдебна система
    case Healthcare = 6010;   // здравеопазване
    case Police = 6020;       // полиция
    case Education = 6030;    // образование (backlog — §1.4)

    public function label(): string
    {
        return match ($this) {
            self::Judiciary => __('enums.sphere.judiciary'),
            self::Healthcare => __('enums.sphere.healthcare'),
            self::Police => __('enums.sphere.police'),
            self::Education => __('enums.sphere.education'),
        };
    }
}
