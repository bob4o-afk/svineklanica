<?php

declare(strict_types=1);

namespace Modules\Presentation\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Presentation\Contracts\PresentationRepository;
use Modules\Presentation\Data\GraphEdgeData;
use Modules\Presentation\Data\GraphNodeData;
use Modules\Detection\Enums\FlagType;
use Modules\Presentation\Data\PlatformStatsData;
use Modules\Presentation\Data\PriceSeriesData;
use Modules\Presentation\Data\RegionAggregateData;
use Modules\Presentation\Data\SerialWinnerGraphData;
use Modules\Presentation\Support\Regions;
use Modules\Procurement\Models\Tender;
use Spatie\LaravelData\Optional;

/**
 * The two flagship visualisations + the map aggregate (contract `/price-series`,
 * `/regions/aggregate`, `/graphs/serial-winner`).
 */
final class InsightController
{
    public function __construct(private readonly PresentationRepository $repo) {}

    public function stats(): PlatformStatsData
    {
        return new PlatformStatsData(
            tenders: $this->repo->countTenders(),
            flags: $this->repo->countFlags(),
            detectors: count(FlagType::cases()),
        );
    }

    public function priceSeries(string $key): PriceSeriesData
    {
        $snapshots = $this->repo->priceSnapshots($key);
        abort_if($snapshots->isEmpty(), 404);

        $first = $snapshots->first();
        $item = $first->tenderItem;
        $tender = $item?->tender;

        return new PriceSeriesData(
            seriesKey: $key,
            productLabel: (string) ($item?->description ?? $first->description ?? $key),
            cpvCode: $tender?->cpv_code ?? Optional::create(),
            unit: $item?->unit ?? Optional::create(),
            points: $snapshots->map(
                static fn ($s) => \Modules\Presentation\Data\PricePointData::fromModel($s)
            )->all(),
        );
    }

    /** @return RegionAggregateData[] */
    public function regions(Request $request): array
    {
        $sector = $request->query('category');
        $sector = is_string($sector) && $sector !== '' ? $sector : null;

        return array_map(
            static fn (array $row): RegionAggregateData => new RegionAggregateData(
                regionCode: $row['region_code'],
                regionName: Regions::name($row['region_code']),
                metric: $row['flag_count'],
                flagCount: $row['flag_count'],
                totalValue: Optional::create(),
            ),
            $this->repo->regionAggregates($sector),
        );
    }

    public function serialWinnerGraph(string $publicId): SerialWinnerGraphData
    {
        $company = $this->repo->findCompanyByPublicId($publicId);
        if ($company === null) {
            // Friendly empty graph (the frontend treats this as "no network"), not a 404.
            return new SerialWinnerGraphData(nodes: [], edges: []);
        }

        /** @var array<string, GraphNodeData> $nodes keyed by node id (public_id) */
        $nodes = [];
        $edges = [];

        // The company under inspection (its win streaks across authorities).
        $wins = Tender::query()
            ->where('winner_company_id', $company->id)
            ->whereNotNull('contracting_authority_id')
            ->with('authority')
            ->get()
            ->groupBy('contracting_authority_id');

        $nodes[$company->public_id] = new GraphNodeData(
            id: $company->public_id,
            kind: 'company',
            label: $company->name,
            publicId: $company->public_id,
            eik: (string) $company->eik,
            winCount: $wins->flatten()->count(),
            clusterId: Optional::create(),
        );

        foreach ($wins as $tenders) {
            $authority = $tenders->first()->authority;
            if ($authority === null) {
                continue;
            }
            $nodes[$authority->public_id] = new GraphNodeData(
                id: $authority->public_id,
                kind: 'authority',
                label: $authority->name,
                publicId: $authority->public_id,
                eik: Optional::create(),
                winCount: Optional::create(),
                clusterId: Optional::create(),
            );
            $edges[] = new GraphEdgeData(
                id: $company->public_id.'->'.$authority->public_id,
                source: $company->public_id,
                target: $authority->public_id,
                weight: $tenders->count(),
                label: $tenders->count().' поръчки',
            );
        }

        // The shell-cluster: other companies that also won from those same authorities.
        foreach ($this->repo->relatedCompanies($company) as $related) {
            $relatedWins = Tender::query()
                ->where('winner_company_id', $related->id)
                ->whereIn('contracting_authority_id', array_keys($wins->all()))
                ->with('authority')
                ->get()
                ->groupBy('contracting_authority_id');

            if ($relatedWins->isEmpty()) {
                continue;
            }

            $nodes[$related->public_id] = new GraphNodeData(
                id: $related->public_id,
                kind: 'company',
                label: $related->name,
                publicId: $related->public_id,
                eik: (string) $related->eik,
                winCount: $relatedWins->flatten()->count(),
                clusterId: $company->public_id,
            );

            foreach ($relatedWins as $authorityId => $tenders) {
                $authority = $tenders->first()->authority;
                if ($authority === null) {
                    continue;
                }
                $edges[] = new GraphEdgeData(
                    id: $related->public_id.'->'.$authority->public_id,
                    source: $related->public_id,
                    target: $authority->public_id,
                    weight: $tenders->count(),
                    label: $tenders->count().' поръчки',
                );
            }
        }

        return new SerialWinnerGraphData(nodes: array_values($nodes), edges: $edges);
    }
}
