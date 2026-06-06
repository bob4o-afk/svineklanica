<?php

declare(strict_types=1);

namespace Modules\Publishing\Enums;

use App\Shared\Contracts\HasLabel;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Editorial state of a corruption post (backend.md §14):
 * Draft = pending (just created, not public) → Published (live in the feed) → Archived.
 * Block 4000.
 */
#[TypeScript]
enum PostStatus: int implements HasLabel
{
    case Draft = 4000;
    case Published = 4010;
    case Archived = 4020;

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('enums.post_status.draft'),
            self::Published => __('enums.post_status.published'),
            self::Archived => __('enums.post_status.archived'),
        };
    }
}
