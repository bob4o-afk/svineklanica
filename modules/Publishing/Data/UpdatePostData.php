<?php

declare(strict_types=1);

namespace Modules\Publishing\Data;

use Illuminate\Validation\Rule;
use Modules\Publishing\Enums\PostStatus;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Update-post input (full replace). Admin only — authorized at the boundary. */
#[TypeScript]
final class UpdatePostData extends Data
{
    /** @param array<int, string>|null $sourceUrls */
    public function __construct(
        public string $title,
        public ?string $excerpt,
        public string $body,
        public PostStatus $status,
        #[LiteralTypeScriptType('string[] | null')]
        public ?array $sourceUrls,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'status' => ['required', Rule::enum(PostStatus::class)],
            'sourceUrls' => ['nullable', 'array'],
            'sourceUrls.*' => ['url'],
        ];
    }

    public static function authorize(): bool
    {
        return request()->user()?->isAdmin() ?? false;
    }
}
