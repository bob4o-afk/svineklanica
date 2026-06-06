<?php

declare(strict_types=1);

namespace Modules\Detection\Data;

use App\Shared\Enums\CorruptionCategory;
use App\Shared\Enums\FlagSeverity;
use App\Shared\Enums\Sphere;
use Modules\Detection\Enums\FlagType;
use Modules\Detection\Models\Flag;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * API shape of a flag — the core hierarchy Sphere → Category → severity band + the
 * % score (CLAUDE.md §1.0). Exposes public_id, never the internal id (backend.md §7).
 */
#[TypeScript]
final class FlagData extends Data
{
    /**
     * @param  array<int, string>  $sourceUrls
     * @param  array<string, mixed>|null  $evidence
     */
    public function __construct(
        public string $publicId,
        public FlagType $type,
        public ?Sphere $sphere,
        public ?CorruptionCategory $category,
        public int $score,
        public FlagSeverity $severity,
        public string $subjectType,
        public ?string $subjectLabel,
        public string $explanationBg,
        #[LiteralTypeScriptType('string[]')]
        public array $sourceUrls,
        #[LiteralTypeScriptType('Record<string, unknown> | null')]
        public ?array $evidence,
        public ?string $detectedAt,
    ) {}

    public static function fromModel(Flag $flag): self
    {
        return new self(
            publicId: $flag->public_id,
            type: $flag->type,
            sphere: $flag->sphere,
            category: $flag->category,
            score: (int) $flag->score,
            severity: $flag->severity,
            subjectType: $flag->subject_type,
            subjectLabel: $flag->subject_label,
            explanationBg: $flag->explanation_bg,
            sourceUrls: $flag->source_urls ?? [],
            evidence: $flag->evidence,
            detectedAt: $flag->detected_at?->toIso8601String(),
        );
    }
}
