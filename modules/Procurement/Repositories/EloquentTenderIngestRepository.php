<?php

declare(strict_types=1);

namespace Modules\Procurement\Repositories;

use Modules\Procurement\Contracts\TenderIngestRepository;
use Modules\Procurement\Models\Company;
use Modules\Procurement\Models\ContractingAuthority;
use Modules\Procurement\Models\Tender;

final class EloquentTenderIngestRepository implements TenderIngestRepository
{
    public function upsertAuthority(array $data): ?ContractingAuthority
    {
        $name = $data['name'] ?? null;
        $eik = $data['eik'] ?? null;
        if ($name === null && $eik === null) {
            return null;
        }

        $match = $eik !== null ? ['eik' => $eik] : ['name' => $name];

        return ContractingAuthority::updateOrCreate($match, $this->withoutNulls([
            'name' => $name,
            'eik' => $eik,
            'region' => $data['region'] ?? null,
            'source_url' => $data['source_url'] ?? null,
        ]));
    }

    public function upsertCompany(array $data): ?Company
    {
        $name = $data['name'] ?? null;
        $eik = $data['eik'] ?? null;
        if ($name === null && $eik === null) {
            return null;
        }

        $match = $eik !== null ? ['eik' => $eik] : ['name' => $name];

        return Company::updateOrCreate($match, $this->withoutNulls([
            'name' => $name,
            'eik' => $eik,
            'address' => $data['address'] ?? null,
            'owner_name' => $data['owner_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'source_url' => $data['source_url'] ?? null,
        ]));
    }

    public function upsertTender(string $source, string $naturalKey, array $attributes): Tender
    {
        return Tender::updateOrCreate(
            ['source' => $source, 'natural_key' => $naturalKey],
            $attributes,
        );
    }

    public function syncItems(Tender $tender, array $items): void
    {
        // Idempotent: a re-ingest replaces the tender's items wholesale.
        $tender->items()->delete();

        foreach ($items as $item) {
            $tender->items()->create($this->withoutNulls([
                'description' => $item['description'] ?? null,
                'quantity' => $item['quantity'] ?? null,
                'unit' => $item['unit'] ?? null,
                'unit_price' => $item['unit_price'] ?? null,
                'currency' => $item['currency'] ?? null,
                'vat_included' => $item['vat_included'] ?? null,
                'source_url' => $item['source_url'] ?? null,
            ]));
        }
    }

    /**
     * Drop null values so an upsert never overwrites an existing column with null
     * (a later, sparser scrape shouldn't erase data from a richer one).
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function withoutNulls(array $attributes): array
    {
        return array_filter($attributes, static fn (mixed $v): bool => $v !== null);
    }
}
