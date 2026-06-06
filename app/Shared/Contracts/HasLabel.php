<?php

declare(strict_types=1);

namespace App\Shared\Contracts;

/**
 * Project rule: every enum is INT-backed (never string values) and exposes a
 * human `label()`. The int is what's stored in the DB and shipped to TypeScript;
 * the label is the Bulgarian-first display string (resolved via i18n, backend.md §10).
 */
interface HasLabel
{
    public function label(): string;
}
