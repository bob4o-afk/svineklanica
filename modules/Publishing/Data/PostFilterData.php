<?php

declare(strict_types=1);

namespace Modules\Publishing\Data;

use App\Shared\Enums\CorruptionCategory;
use App\Shared\Enums\FlagSeverity;
use App\Shared\Enums\Sphere;
use Illuminate\Validation\Rule;
use Modules\Publishing\Enums\PostTag;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Query filter for the public posts feed — Sphere → Category → Severity (CLAUDE.md
 * §1.0), plus an optional punk tag (§1.0.1). All input validated AND authorized
 * here (backend.md §4/§6); public read-only, the route still rate-limits.
 */
#[TypeScript]
final class PostFilterData extends Data
{
    public function __construct(
        public ?Sphere $sphere = null,
        public ?CorruptionCategory $category = null,
        public ?FlagSeverity $severity = null,
        public ?PostTag $tag = null,
        public int $perPage = 15,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'sphere' => ['nullable', Rule::enum(Sphere::class)],
            'category' => ['nullable', Rule::enum(CorruptionCategory::class)],
            'severity' => ['nullable', Rule::enum(FlagSeverity::class)],
            'tag' => ['nullable', Rule::enum(PostTag::class)],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public static function authorize(): bool
    {
        return true; // public, read-only feed
    }
}
