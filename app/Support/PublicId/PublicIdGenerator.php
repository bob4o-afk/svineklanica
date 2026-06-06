<?php

declare(strict_types=1);

namespace App\Support\PublicId;

use Illuminate\Support\Str;

/**
 * The public identifier is a UUIDv7, generated explicitly in PHP (never a DB
 * default) so ids are time-ordered, index-friendly, and stable across
 * environments (backend.md §7).
 */
final class PublicIdGenerator
{
    public static function generate(): string
    {
        return Str::uuid7()->toString();
    }
}
