<?php

declare(strict_types=1);

namespace Modules\Detection\Detectors\Contracts;

use Modules\Detection\Enums\FlagType;

/**
 * A red-flag detector (CLAUDE.md §1.1, backend.md §11). Deterministic and
 * re-runnable: each run() recomputes the flags of its {@see type()} from scratch.
 * The UI reads the precomputed flags; detectors run out-of-band as queued jobs.
 */
interface Detector
{
    /** The single flag type this detector emits. */
    public function type(): FlagType;

    /** Recompute this detector's flags; returns how many were written. */
    public function run(): int;
}
