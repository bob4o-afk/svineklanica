<?php

declare(strict_types=1);

namespace Modules\Presentation\Data;

use Modules\Detection\Enums\FlagType;
use Modules\Detection\Models\Flag;
use Modules\Presentation\Support\ContractEnums;
use Modules\Procurement\Models\Company;
use Modules\Procurement\Models\ContractingAuthority;
use Modules\Procurement\Models\Tender;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

/**
 * Contract `FlagPost` — the citizen-facing shape of a red-flag (apps/web `contract.ts`).
 * The presentation seam projects the domain Flag (+ its morph subject) into the rich,
 * snake_case, string-enum shape the React app consumes. Absent optionals are OMITTED
 * (Optional), never null — the UI tests fields with `!== undefined`.
 */
#[MapName(SnakeCaseMapper::class)]
final class FlagPostData extends Data
{
    /**
     * @param  EvidenceItemData[]  $evidence
     * @param  SourceRefData[]  $sources
     */
    public function __construct(
        public string $publicId,
        public string $type,
        public string|Optional $category,
        public string $severity,
        public string $status,
        public FlagSubjectData $subject,
        public string|Optional $title,
        public string $explanationBg,
        public array $evidence,
        public array $sources,
        public string $detectedAt,
        public string|Optional $publishedAt,
        public string|Optional $seriesKey,
        public int $viewCount,
    ) {}

    public static function fromModel(Flag $flag, int $viewCount = 0): self
    {
        $subject = $flag->subject;

        $tender = null;
        $authority = null;
        $company = null;

        if ($subject instanceof Tender) {
            $tender = $subject;
            $authority = $subject->authority;
            $company = $subject->winner;
        } elseif ($subject instanceof Company) {
            $company = $subject;
            $tender = $subject->wonTenders->first();
            $authority = $tender?->authority;
        } elseif ($subject instanceof ContractingAuthority) {
            $authority = $subject;
            $tender = $subject->tenders->first();
            $company = $tender?->winner;
        }

        $subjectData = new FlagSubjectData(
            type: $flag->subject_type,
            authority: $authority !== null ? AuthorityRefData::fromModel($authority) : Optional::create(),
            company: $company !== null ? CompanyRefData::fromModel($company) : Optional::create(),
            tender: $tender !== null ? TenderRefData::fromModel($tender) : Optional::create(),
        );

        // Display the SAME sector the feed filters on (the denormalized `sector` column),
        // never a value recomputed from CPV here — otherwise a flag matched by `?category=x`
        // could render a badge for a different sector. Null sector → no badge (and it can't
        // be matched by any sector filter either, so the two stay consistent).
        $category = ($flag->sector !== null && $flag->sector !== '')
            ? $flag->sector
            : Optional::create();

        $detectedAt = ($flag->detected_at ?? $flag->created_at)?->toIso8601String() ?? '';

        $seriesKey = ($flag->type === FlagType::PriceDiscrepancy && $flag->series_key !== null)
            ? $flag->series_key
            : Optional::create();

        return new self(
            publicId: $flag->public_id,
            type: ContractEnums::flagType($flag->type),
            category: $category,
            severity: ContractEnums::severity($flag->severity),
            status: 'approved',
            subject: $subjectData,
            title: $flag->title ?? Optional::create(),
            explanationBg: $flag->explanation_bg,
            evidence: self::evidence($flag),
            sources: self::sources($flag, $detectedAt),
            detectedAt: $detectedAt,
            publishedAt: $detectedAt,
            seriesKey: $seriesKey,
            viewCount: $viewCount,
        );
    }

    /** @return EvidenceItemData[] */
    private static function evidence(Flag $flag): array
    {
        $rows = $flag->evidence ?? [];
        // A flat list of {label,value,money?} rows is the contract shape; tolerate an
        // assoc map by skipping it (older detector output) rather than mis-rendering.
        if (! array_is_list($rows)) {
            return [];
        }

        return array_map(
            static fn (array $row): EvidenceItemData => EvidenceItemData::fromArray($row),
            $rows,
        );
    }

    /** @return SourceRefData[] */
    private static function sources(Flag $flag, string $fetchedAt): array
    {
        return array_map(
            static fn (string $url): SourceRefData => new SourceRefData(
                url: $url,
                label: 'Първоизточник',
                fetchedAt: $fetchedAt,
            ),
            $flag->source_urls ?? [],
        );
    }
}
