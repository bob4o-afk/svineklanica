<?php

declare(strict_types=1);

namespace Modules\Detection\Support;

use App\Shared\DTO\TenderSubjectData;
use App\Shared\Enums\CorruptionCategory;
use App\Shared\Enums\FlagSeverity;
use App\Shared\Enums\Sphere;
use Modules\Detection\Enums\FlagType;
use Modules\Detection\Models\Flag;

/**
 * Maps one AI verdict (the apps/ai analyzer's NDJSON line, data-sources.md §4 shape)
 * onto a {@see Flag} row. The analyzer's rich flag/level
 * taxonomy is projected onto the backend's seven {@see FlagType} cases; the 0–100
 * `corruption_score` drives the stored score + derived {@see FlagSeverity} band.
 *
 * Flags written here carry `evidence.origin = 'ai'` so they re-ingest idempotently
 * without clobbering the deterministic detectors' flags.
 */
final class VerdictFlagMapper
{
    /** AI flag-type string (schemas.py) → backend FlagType (the 7 detectors). */
    private const FLAG_TYPE_MAP = [
        'price_discrepancy' => FlagType::PriceDiscrepancy,
        'drug_overpricing' => FlagType::PriceDiscrepancy,
        'tailored_spec' => FlagType::TailoredSpec,
        'inn_steering' => FlagType::TailoredSpec,
        'rigged_competition' => FlagType::TailoredSpec,
        'serial_winner' => FlagType::SerialWinner,
        'collusion' => FlagType::SerialWinner,
        'conflict_of_interest' => FlagType::SerialWinner,
        'cancelled' => FlagType::Cancelled,
        'procedure_abuse' => FlagType::Cancelled,
        'threshold_manipulation' => FlagType::Cancelled,
        'delayed_payment' => FlagType::DelayedPayment,
        'doc_clone' => FlagType::DocClone,
        'amendment_abuse' => FlagType::DocClone,
        // everything scope-/value-shaped folds into implausible scope (the catch-all)
        'implausible_scope' => FlagType::ImplausibleScope,
        'undervalued_sale' => FlagType::ImplausibleScope,
        'project_abuse' => FlagType::ImplausibleScope,
        'concession_abuse' => FlagType::ImplausibleScope,
        'audit_finding' => FlagType::ImplausibleScope,
        'unexplained_wealth' => FlagType::ImplausibleScope,
        'donor_influence' => FlagType::ImplausibleScope,
    ];

    private const SPHERE_MAP = [
        'съдебна система' => Sphere::Judiciary,
        'здравеопазване' => Sphere::Healthcare,
        'полиция' => Sphere::Police,
        'образование' => Sphere::Education,
    ];

    private const CATEGORY_MAP = [
        'обществена поръчка' => CorruptionCategory::PublicProcurement,
        'нерегламентирани плащания' => CorruptionCategory::UnregulatedPayment,
    ];

    /**
     * @param  array<string, mixed>  $verdict  one decoded verdict NDJSON line
     * @return array<string, mixed> a Flag row (fillable shape)
     */
    public function toFlagRow(array $verdict, TenderSubjectData $tender): array
    {
        $score = (int) round((float) ($verdict['corruption_score'] ?? 0));
        $score = max(0, min(100, $score));

        /** @var array<int, array<string, mixed>> $aiFlags */
        $aiFlags = is_array($verdict['flags'] ?? null) ? $verdict['flags'] : [];

        return [
            'type' => $this->resolveType($aiFlags),
            'sphere' => $tender->sphere ?? $this->mapSphere($verdict['sphere'] ?? null),
            'category' => $tender->category ?? $this->mapCategory($verdict['category'] ?? null),
            'score' => $score,
            'severity' => FlagSeverity::fromScore($score),
            'subject_type' => 'tender',
            'subject_id' => $tender->tenderId,
            'subject_label' => $tender->label,
            'title' => $this->resolveTitle($verdict, $tender),
            'explanation_bg' => (string) ($verdict['explanation_bg'] ?? ''),
            'source_urls' => $this->resolveSourceUrls($verdict, $tender),
            'evidence' => [
                'origin' => 'ai',
                'level' => (string) ($verdict['level'] ?? ''),
                'score' => (float) ($verdict['corruption_score'] ?? 0),
                'flow_key' => (string) ($verdict['flow_key'] ?? ''),
                'model' => (string) ($verdict['model'] ?? ''),
                'ai_flags' => array_values(array_filter(array_map(
                    static fn (array $f): ?string => isset($f['type']) ? (string) $f['type'] : null,
                    $aiFlags,
                ))),
                'top_signals' => $this->topSignals($verdict),
            ],
            'detected_at' => now(),
        ];
    }

    /** @param array<int, array<string, mixed>> $aiFlags */
    private function resolveType(array $aiFlags): FlagType
    {
        foreach ($aiFlags as $flag) {
            $key = isset($flag['type']) ? (string) $flag['type'] : '';
            if (isset(self::FLAG_TYPE_MAP[$key])) {
                return self::FLAG_TYPE_MAP[$key];
            }
        }

        // No mapped flag (e.g. a low-risk "Нормално" verdict) → the catch-all.
        return FlagType::ImplausibleScope;
    }

    private function mapSphere(mixed $value): ?Sphere
    {
        return is_string($value) ? (self::SPHERE_MAP[$value] ?? null) : null;
    }

    private function mapCategory(mixed $value): ?CorruptionCategory
    {
        return is_string($value) ? (self::CATEGORY_MAP[$value] ?? null) : null;
    }

    /** @param array<string, mixed> $verdict */
    private function resolveTitle(array $verdict, TenderSubjectData $tender): string
    {
        $headline = trim((string) ($verdict['headline_bg'] ?? ''));

        return $headline !== '' ? $headline : $tender->label;
    }

    /**
     * @param  array<string, mixed>  $verdict
     * @return array<int, string>
     */
    private function resolveSourceUrls(array $verdict, TenderSubjectData $tender): array
    {
        $urls = [];
        $verdictUrl = trim((string) ($verdict['source_url'] ?? ''));
        if ($verdictUrl !== '') {
            $urls[] = $verdictUrl;
        }
        if ($tender->sourceUrl !== '' && ! in_array($tender->sourceUrl, $urls, true)) {
            $urls[] = $tender->sourceUrl;
        }

        return $urls;
    }

    /**
     * @param  array<string, mixed>  $verdict
     * @return array<int, array<string, mixed>> the few strongest auditable signals
     */
    private function topSignals(array $verdict): array
    {
        $signals = is_array($verdict['signals'] ?? null) ? $verdict['signals'] : [];

        usort(
            $signals,
            static fn (array $a, array $b): int => ((float) ($b['contribution'] ?? 0)) <=> ((float) ($a['contribution'] ?? 0)),
        );

        return array_map(
            static fn (array $s): array => [
                'key' => (string) ($s['key'] ?? ''),
                'family' => (string) ($s['family'] ?? ''),
                'risk' => (float) ($s['risk'] ?? 0),
                'rationale_bg' => (string) ($s['rationale_bg'] ?? ''),
            ],
            array_slice($signals, 0, 5),
        );
    }
}
