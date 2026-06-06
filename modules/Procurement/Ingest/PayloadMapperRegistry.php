<?php

declare(strict_types=1);

namespace Modules\Procurement\Ingest;

/**
 * Dispatches a canonical payload to the right {@see PayloadMapper} by its
 * `record_type` (contract.py v2). Only `tender` and `payment` project into domain
 * tables today; every other (or missing) `record_type` goes to the provenance-only
 * mapper — ingested + searchable in `ingest_records`, no domain row, no junk.
 */
final class PayloadMapperRegistry
{
    /** @var array<string, PayloadMapper> */
    private array $byType = [];

    public function __construct(
        TenderPayloadMapper $tender,
        PaymentPayloadMapper $payment,
        private readonly NullPayloadMapper $provenanceOnly,
    ) {
        $this->register($tender);
        $this->register($payment);
    }

    private function register(PayloadMapper $mapper): void
    {
        $this->byType[$mapper->recordType()] = $mapper;
    }

    public function for(?string $recordType): PayloadMapper
    {
        return $this->byType[$recordType] ?? $this->provenanceOnly;
    }
}
