<?php

declare(strict_types=1);

namespace Modules\Detection\Models;

use App\Support\PublicId\HasPublicId;
use Database\Factories\FlagFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Detection\Enums\FlagSeverity;
use Modules\Detection\Enums\FlagType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A red-flag claim (CLAUDE.md §1.1). `source_urls` is mandatory — a detector
 * never asserts a flag without one (backend.md §11, data-sources.md §0).
 */
#[TypeScript]
final class Flag extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $fillable = [
        'type',
        'severity',
        'subject_type',
        'subject_id',
        'subject_label',
        'explanation_bg',
        'source_urls',
        'evidence',
        'detected_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => FlagType::class,
            'severity' => FlagSeverity::class,
            'source_urls' => 'array',
            'evidence' => 'array',
            'detected_at' => 'datetime',
        ];
    }

    /** The flagged entity: a tender, company, or contracting authority. @return MorphTo<Model, Flag> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory(): Factory
    {
        return FlagFactory::new();
    }
}
