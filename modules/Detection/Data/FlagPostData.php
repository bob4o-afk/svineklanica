<?php

declare(strict_types=1);

namespace Modules\Detection\Data;

use Illuminate\Support\Arr;
use Modules\Detection\Enums\ApprovalStatus;
use Modules\Detection\Models\Flag;
use Modules\Procurement\Models\Company;
use Modules\Procurement\Models\ContractingAuthority;
use Modules\Procurement\Models\Tender;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Public/admin flag-post shape (contract.ts FlagPost, snake_case JSON). */
#[TypeScript]
#[MapOutputName(SnakeCaseMapper::class)]
final class FlagPostData extends Data
{
    /**
     * @param array<int, array<string, mixed>> $evidence
     * @param array<int, array<string, mixed>> $sources
     * @param array<int, string>|null $tags
     */
    public function __construct(
        public string $public_id,
        public string $type,
        public string $severity,
        public string $status,
        public array $subject,
        public string $explanation_bg,
        public array $evidence,
        public array $sources,
        public string $detected_at,
        public ?string $title = null,
        public ?string $category = null,
        public ?string $published_at = null,
        public ?string $series_key = null,
        public ?array $tags = null,
    ) {}

    public static function fromModel(Flag $flag): self
    {
        return new self(
            public_id: $flag->public_id,
            type: $flag->type->wire(),
            severity: $flag->severity->wire(),
            status: $flag->status->wire(),
            subject: self::mapSubject($flag),
            explanation_bg: $flag->explanation_bg,
            evidence: self::mapEvidence($flag->evidence ?? []),
            sources: self::mapSources($flag),
            detected_at: $flag->detected_at->toIso8601String(),
            title: $flag->title,
            category: $flag->category,
            published_at: $flag->published_at?->toIso8601String(),
            series_key: $flag->series_key,
            tags: $flag->tags,
        );
    }

    /** @return array<string, mixed> */
    private static function mapSubject(Flag $flag): array
    {
        $subject = $flag->subject;

        if ($subject instanceof Tender) {
            $authority = $subject->authority;

            return array_filter([
                'type' => 'tender',
                'tender' => array_filter([
                    'public_id' => $subject->public_id,
                    'title' => $subject->title,
                    'cpv_code' => $subject->cpv_code,
                    'ted_id' => $subject->source === 'ted' ? $subject->natural_key : null,
                ]),
                'authority' => $authority ? array_filter([
                    'public_id' => $authority->public_id,
                    'name' => $authority->name,
                    'region_code' => $authority->region,
                ]) : null,
            ], static fn ($v) => $v !== null);
        }

        if ($subject instanceof ContractingAuthority) {
            return [
                'type' => 'authority',
                'authority' => array_filter([
                    'public_id' => $subject->public_id,
                    'name' => $subject->name,
                    'region_code' => $subject->region,
                ]),
            ];
        }

        if ($subject instanceof Company) {
            return [
                'type' => 'company',
                'company' => [
                    'public_id' => $subject->public_id,
                    'eik' => $subject->eik,
                    'name' => $subject->name,
                ],
            ];
        }

        return [
            'type' => 'tender',
            'tender' => [
                'public_id' => $flag->public_id,
                'title' => $flag->subject_label ?? 'Обществена поръчка',
            ],
        ];
    }

    /** @param array<string, mixed> $raw @return array<int, array<string, mixed>> */
    private static function mapEvidence(array $raw): array
    {
        if (isset($raw['signals']) && is_array($raw['signals'])) {
            return array_values(array_map(static function (array $signal): array {
                return array_filter([
                    'label' => $signal['key'] ?? 'signal',
                    'value' => $signal['value'] ?? ($signal['risk'] ?? ''),
                ]);
            }, $raw['signals']));
        }

        return array_values(array_map(static function ($value, $key): array {
            return ['label' => (string) $key, 'value' => is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE)];
        }, $raw, array_keys($raw)));
    }

    /** @return array<int, array<string, string>> */
    private static function mapSources(Flag $flag): array
    {
        $urls = Arr::wrap($flag->source_urls);
        $fetched = $flag->detected_at->toIso8601String();

        return array_values(array_map(static fn (string $url): array => [
            'url' => $url,
            'label' => parse_url($url, PHP_URL_HOST) ?: $url,
            'fetched_at' => $fetched,
        ], array_filter($urls, static fn ($u) => is_string($u) && $u !== '')));
    }
}
