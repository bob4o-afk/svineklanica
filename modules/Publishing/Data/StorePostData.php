<?php

declare(strict_types=1);

namespace Modules\Publishing\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Create-post input. Authorization is enforced HERE (backend.md §4/§6) — admin only. */
#[TypeScript]
final class StorePostData extends Data
{
    /** @param array<int, string>|null $sourceUrls */
    public function __construct(
        public string $title,
        public ?string $excerpt,
        public string $body,
        public ?array $sourceUrls,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'sourceUrls' => ['nullable', 'array'],
            'sourceUrls.*' => ['url'],
        ];
    }

    public static function authorize(): bool
    {
        return request()->user()?->isAdmin() ?? false;
    }
}
