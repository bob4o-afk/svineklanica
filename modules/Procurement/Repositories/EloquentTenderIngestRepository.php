<?php

declare(strict_types=1);

namespace Modules\Procurement\Repositories;

use Carbon\CarbonInterface;
use Modules\Procurement\Contracts\TenderIngestRepository;
use Modules\Procurement\Models\Company;
use Modules\Procurement\Models\ContractingAuthority;
use Modules\Procurement\Models\PriceSnapshot;
use Modules\Procurement\Models\Tender;
use Modules\Procurement\Support\ProductKey;

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

    public function syncItems(Tender $tender, array $items, CarbonInterface $capturedAt): void
    {
        // Idempotent: a re-ingest replaces the tender's items + their snapshots wholesale.
        $oldItemIds = $tender->items()->pluck('id');
        if ($oldItemIds->isNotEmpty()) {
            PriceSnapshot::query()->whereIn('tender_item_id', $oldItemIds)->delete();
        }
        $tender->items()->delete();

        foreach ($items as $item) {
            $created = $tender->items()->create($this->withoutNulls([
                'description' => $item['description'] ?? null,
                'quantity' => $item['quantity'] ?? null,
                'unit' => $item['unit'] ?? null,
                'unit_price' => $item['unit_price'] ?? null,
                'currency' => $item['currency'] ?? null,
                'vat_included' => $item['vat_included'] ?? null,
                'source_url' => $item['source_url'] ?? null,
            ]));

            // A priced, named item becomes a point-in-time price snapshot.
            $unitPrice = $item['unit_price'] ?? null;
            $productKey = ProductKey::normalize($item['description'] ?? null);
            if ($unitPrice !== null && $productKey !== null) {
                $created->priceSnapshots()->create([
                    'product_key' => $productKey,
                    'description' => (string) ($item['description'] ?? ''),
                    'price' => $unitPrice,
                    'currency' => $item['currency'] ?? $tender->currency ?? 'BGN',
                    'captured_at' => $capturedAt,
                    'source_url' => $item['source_url'] ?? $tender->source_url,
                ]);
            }
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
