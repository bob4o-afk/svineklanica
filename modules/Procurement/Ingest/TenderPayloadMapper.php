<?php

declare(strict_types=1);

namespace Modules\Procurement\Ingest;

use Carbon\CarbonInterface;
use Modules\Procurement\Contracts\TenderIngestRepository;
use Modules\Procurement\Enums\TenderStatus;
use Modules\Procurement\Services\SphereClassifier;

/**
 * Maps a `record_type: "tender"` canonical payload (contract.py v2) onto the
 * tender aggregate. The canonical shape:
 *   { title, description?, sphere?, category,
 *     authority?: { name, eik?, region?, source_url? },
 *     winner?:    { name, eik?, address?, owner_name?, phone?, source_url? },
 *     tender: { cpv_code?, value?, currency?, vat_included?, status?,
 *               announced_at?, deadline_at?, awarded_at?, cancelled_at?, items?[] } }
 */
final class TenderPayloadMapper implements PayloadMapper
{
    public function __construct(
        private readonly TenderIngestRepository $tenders,
        private readonly SphereClassifier $classifier,
    ) {}

    public function recordType(): string
    {
        return 'tender';
    }

    public function map(
        string $source,
        string $naturalKey,
        string $sourceUrl,
        CarbonInterface $fetchedAt,
        array $payload,
    ): void {
        $authority = isset($payload['authority']) && is_array($payload['authority'])
            ? $this->tenders->upsertAuthority($payload['authority'])
            : null;

        $winner = isset($payload['winner']) && is_array($payload['winner'])
            ? $this->tenders->upsertCompany($payload['winner'])
            : null;

        /** @var array<string, mixed> $detail */
        $detail = isset($payload['tender']) && is_array($payload['tender']) ? $payload['tender'] : [];

        // Trust the scraper's sphere/category strings; derive only when absent (§ resolve).
        $classification = $this->classifier->resolve(
            isset($payload['sphere']) ? (string) $payload['sphere'] : null,
            isset($payload['category']) ? (string) $payload['category'] : null,
            $authority?->name,
            isset($detail['cpv_code']) ? (string) $detail['cpv_code'] : null,
            $source,
        );

        $tender = $this->tenders->upsertTender($source, $naturalKey, [
            'source_url' => $sourceUrl,
            'fetched_at' => $fetchedAt,
            'contracting_authority_id' => $authority?->id,
            'winner_company_id' => $winner?->id,
            'title' => (string) ($payload['title'] ?? '(без заглавие)'),
            'description' => $payload['description'] ?? null,
            'cpv_code' => $detail['cpv_code'] ?? null,
            'sphere' => $classification->sphere,
            'category' => $classification->category,
            'value' => $detail['value'] ?? null,
            'currency' => $detail['currency'] ?? null,
            'vat_included' => $detail['vat_included'] ?? null,
            'status' => $this->mapStatus($detail['status'] ?? null),
            'announced_at' => $detail['announced_at'] ?? null,
            'deadline_at' => $detail['deadline_at'] ?? null,
            'awarded_at' => $detail['awarded_at'] ?? null,
            'cancelled_at' => $detail['cancelled_at'] ?? null,
        ]);

        $items = isset($detail['items']) && is_array($detail['items']) ? $detail['items'] : [];
        $this->tenders->syncItems($tender, $items, $fetchedAt);
    }

    private function mapStatus(mixed $status): TenderStatus
    {
        return match (is_string($status) ? strtolower($status) : '') {
            'open' => TenderStatus::Open,
            'awarded' => TenderStatus::Awarded,
            'cancelled' => TenderStatus::Cancelled,
            'terminated' => TenderStatus::Terminated,
            default => TenderStatus::Announced,
        };
    }
}
