<?php

declare(strict_types=1);

namespace Modules\Notifications\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Admin-only: broadcast a notification to all subscribers (authorize HERE — backend.md §4). */
#[TypeScript]
final class BroadcastData extends Data
{
    /** @param list<string> $lines */
    public function __construct(
        public string $subject,
        #[LiteralTypeScriptType('string[]')]
        public array $lines = [],
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'lines' => ['array'],
            'lines.*' => ['string', 'max:2000'],
        ];
    }

    public static function authorize(): bool
    {
        return request()->user()?->isAdmin() ?? false;
    }
}
