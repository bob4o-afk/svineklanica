<?php

declare(strict_types=1);

namespace Modules\Detection\Data;

use App\Shared\Enums\CorruptionCategory;
use App\Shared\Enums\FlagSeverity;
use App\Shared\Enums\Sphere;
use Illuminate\Validation\Rule;
use Modules\Detection\Enums\FlagType;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Query filter for the public flag feed — Sphere → Category → Severity (CLAUDE.md
 * §1.0), the primary navigation. All input is validated AND authorized here
 * (backend.md §4/§6). Read-only public endpoint, so any caller may filter; the
 * route still rate-limits + abuse-guards (security.md §1/§2).
 */
#[TypeScript]
final class FlagFilterData extends Data
{
    public function __construct(
        public ?Sphere $sphere = null,
        public ?CorruptionCategory $category = null,
        public ?FlagSeverity $severity = null,
        public ?FlagType $type = null,
        public ?int $minScore = null,
        public int $perPage = 15,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'sphere' => ['nullable', Rule::enum(Sphere::class)],
            'category' => ['nullable', Rule::enum(CorruptionCategory::class)],
            'severity' => ['nullable', Rule::enum(FlagSeverity::class)],
            'type' => ['nullable', Rule::enum(FlagType::class)],
            'minScore' => ['nullable', 'integer', 'min:0', 'max:100'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public static function authorize(): bool
    {
        return true; // public, read-only feed
    }
}
