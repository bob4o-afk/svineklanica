<?php

declare(strict_types=1);

namespace Modules\Publishing\Data;

use App\Shared\Enums\CorruptionCategory;
use App\Shared\Enums\FlagSeverity;
use App\Shared\Enums\Sphere;
use Illuminate\Validation\Rule;
use Modules\Publishing\Enums\PostTag;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Create-post input. Authorization is enforced HERE (backend.md §4/§6) — admin only. */
#[TypeScript]
final class StorePostData extends Data
{
    /**
     * @param  array<int, string>|null  $sourceUrls
     * @param  array<int, int>|null  $tags  PostTag values (punk badges, CLAUDE.md §1.0.1)
     */
    public function __construct(
        public string $title,
        public ?string $excerpt,
        public string $body,
        public ?Sphere $sphere,
        public ?CorruptionCategory $category,
        public ?FlagSeverity $severity,
        #[LiteralTypeScriptType('number[] | null')]
        public ?array $tags,
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
            'sphere' => ['nullable', Rule::enum(Sphere::class)],
            'category' => ['nullable', Rule::enum(CorruptionCategory::class)],
            'severity' => ['nullable', Rule::enum(FlagSeverity::class)],
            'tags' => ['nullable', 'array'],
            'tags.*' => [Rule::enum(PostTag::class)],
            'sourceUrls' => ['nullable', 'array'],
            'sourceUrls.*' => ['url'],
        ];
    }

    public static function authorize(): bool
    {
        return request()->user()?->isAdmin() ?? false;
    }
}
