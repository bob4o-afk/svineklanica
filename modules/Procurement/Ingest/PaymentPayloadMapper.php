<?php

declare(strict_types=1);

namespace Modules\Procurement\Ingest;

use Carbon\CarbonInterface;
use Modules\Procurement\Contracts\PaymentIngestRepository;
use Modules\Procurement\Contracts\TenderIngestRepository;
use Modules\Procurement\Services\SphereClassifier;

/**
 * Maps a `record_type: "payment"` canonical payload (contract.py v2) onto the
 * payments table. The canonical shape:
 *   { title, description?, sphere?, category,
 *     authority?: { name, eik?, region? },   // the spender, denormalized
 *     winner?:    { name, eik?, address? },   // the recipient, denormalized
 *     payment: { spender, recipient?, amount?, currency?, paid_at?, purpose? } }
 *
 * Spender → contracting_authorities, recipient → companies (shared tables, so the
 * upserts go through TenderIngestRepository — the single owner of those tables).
 * The amount lands on payments.amount, which feeds the corruption-tax calculator.
 */
final class PaymentPayloadMapper implements PayloadMapper
{
    public function __construct(
        private readonly PaymentIngestRepository $payments,
        private readonly TenderIngestRepository $tenders,
        private readonly SphereClassifier $classifier,
    ) {}

    public function recordType(): string
    {
        return 'payment';
    }

    public function map(
        string $source,
        string $naturalKey,
        string $sourceUrl,
        CarbonInterface $fetchedAt,
        array $payload,
    ): void {
        /** @var array<string, mixed> $detail */
        $detail = isset($payload['payment']) && is_array($payload['payment']) ? $payload['payment'] : [];

        // Spender (the paying authority) — prefer the structured authority block,
        // fall back to the bare name in the payment detail.
        $spenderData = isset($payload['authority']) && is_array($payload['authority'])
            ? $payload['authority']
            : (isset($detail['spender']) ? ['name' => (string) $detail['spender']] : []);
        $spender = $spenderData !== [] ? $this->tenders->upsertAuthority($spenderData) : null;

        // Recipient (the company paid).
        $recipientData = isset($payload['winner']) && is_array($payload['winner'])
            ? $payload['winner']
            : (isset($detail['recipient']) && $detail['recipient'] !== null
                ? ['name' => (string) $detail['recipient']]
                : []);
        $recipient = $recipientData !== [] ? $this->tenders->upsertCompany($recipientData) : null;

        $classification = $this->classifier->resolve(
            isset($payload['sphere']) ? (string) $payload['sphere'] : null,
            isset($payload['category']) ? (string) $payload['category'] : null,
            $spender?->name,
            null, // payments carry no CPV
            $source,
        );

        $this->payments->upsertPayment($source, $naturalKey, [
            'source_url' => $sourceUrl,
            'fetched_at' => $fetchedAt,
            'spender_authority_id' => $spender?->id,
            'recipient_company_id' => $recipient?->id,
            'title' => (string) ($payload['title'] ?? $detail['purpose'] ?? '(плащане)'),
            'description' => $payload['description'] ?? $detail['purpose'] ?? null,
            'sphere' => $classification->sphere,
            'category' => $classification->category,
            'amount' => $detail['amount'] ?? null,
            'currency' => $detail['currency'] ?? null,
            'paid_at' => $detail['paid_at'] ?? null,
        ]);
    }
}
