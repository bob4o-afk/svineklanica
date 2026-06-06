<?php

declare(strict_types=1);

namespace Modules\Procurement\Repositories;

use App\Shared\Contracts\ProcurementReadPort;
use App\Shared\DTO\CancelledTenderData;
use App\Shared\DTO\CompanyWinData;
use App\Shared\DTO\PriceObservationData;
use App\Shared\DTO\TenderSubjectData;
use App\Shared\Enums\CorruptionCategory;
use App\Shared\Enums\Sphere;
use Illuminate\Support\Facades\DB;
use Modules\Procurement\Enums\TenderStatus;
use Modules\Procurement\Models\Company;
use Modules\Procurement\Models\Tender;

/**
 * Procurement's side of the {@see ProcurementReadPort} seam — reads its own tables
 * and maps to shared DTOs the Detection module consumes (backend.md §1/§2).
 */
final class EloquentProcurementReadRepository implements ProcurementReadPort
{
    public function priceObservations(): array
    {
        $rows = DB::table('price_snapshots')
            ->join('tender_items', 'price_snapshots.tender_item_id', '=', 'tender_items.id')
            ->join('tenders', 'tender_items.tender_id', '=', 'tenders.id')
            ->select([
                'tenders.id as tender_id',
                'tenders.title as tender_title',
                'tenders.sphere as sphere',
                'tenders.category as category',
                'price_snapshots.product_key',
                'price_snapshots.description',
                'price_snapshots.price',
                'price_snapshots.currency',
                'price_snapshots.source_url',
            ])
            ->get();

        return $rows->map(fn (object $r): PriceObservationData => new PriceObservationData(
            tenderId: (int) $r->tender_id,
            tenderLabel: (string) $r->tender_title,
            sourceUrl: (string) $r->source_url,
            productKey: (string) $r->product_key,
            description: (string) $r->description,
            price: (float) $r->price,
            currency: (string) $r->currency,
            sphere: $r->sphere !== null ? Sphere::tryFrom((int) $r->sphere) : null,
            category: $r->category !== null ? CorruptionCategory::tryFrom((int) $r->category) : null,
        ))->all();
    }

    public function serialWinners(int $minWins): array
    {
        $grouped = Tender::query()
            ->whereNotNull('winner_company_id')
            ->groupBy('winner_company_id')
            ->havingRaw('count(*) >= ?', [$minWins])
            ->get([
                'winner_company_id',
                DB::raw('count(*) as win_count'),
                DB::raw('count(distinct contracting_authority_id) as distinct_authorities'),
                DB::raw('min(source_url) as sample_source_url'), // provenance fallback
            ]);

        if ($grouped->isEmpty()) {
            return [];
        }

        $companies = Company::query()
            ->whereIn('id', $grouped->pluck('winner_company_id'))
            ->get()
            ->keyBy('id');

        return $grouped->map(function (Tender $row) use ($companies): ?CompanyWinData {
            $company = $companies->get($row->winner_company_id);
            if ($company === null) {
                return null;
            }

            return new CompanyWinData(
                companyId: (int) $company->id,
                name: (string) $company->name,
                eik: $company->eik,
                sourceUrl: $company->source_url ?? (string) $row->getAttribute('sample_source_url'),
                winCount: (int) $row->getAttribute('win_count'),
                distinctAuthorities: (int) $row->getAttribute('distinct_authorities'),
            );
        })->filter()->values()->all();
    }

    public function cancelledTenders(): array
    {
        return Tender::query()
            ->whereIn('status', [TenderStatus::Cancelled, TenderStatus::Terminated])
            ->get()
            ->map(fn (Tender $t): CancelledTenderData => new CancelledTenderData(
                tenderId: (int) $t->id,
                label: (string) $t->title,
                sourceUrl: (string) $t->source_url,
                sphere: $t->sphere,
                category: $t->category,
                wasTerminated: $t->status === TenderStatus::Terminated,
                statusLabel: $t->status->label(),
                cancelledAt: $t->cancelled_at?->toIso8601String(),
            ))->all();
    }

    public function tenderSubjectsByNaturalKey(string $source): array
    {
        return Tender::query()
            ->where('tenders.source', $source)
            ->leftJoin('contracting_authorities', 'tenders.contracting_authority_id', '=', 'contracting_authorities.id')
            ->get([
                'tenders.id as id',
                'tenders.natural_key as natural_key',
                'tenders.title as title',
                'tenders.source_url as source_url',
                'tenders.cpv_code as cpv_code',
                'tenders.sphere as sphere',
                'tenders.category as category',
                'contracting_authorities.region as region',
            ])
            ->mapWithKeys(fn (Tender $t): array => [
                (string) $t->natural_key => new TenderSubjectData(
                    tenderId: (int) $t->id,
                    label: (string) $t->title,
                    sourceUrl: (string) $t->source_url,
                    cpv: $t->cpv_code !== null ? (string) $t->cpv_code : null,
                    region: $t->region !== null ? (string) $t->region : null,
                    sphere: $t->sphere,
                    category: $t->category,
                ),
            ])
            ->all();
    }
}
