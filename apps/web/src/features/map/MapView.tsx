import { Stack } from '@mui/material';
import { useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { AppChartFrame } from '@/components/charts/AppChartFrame';
import { AppChoroplethMap } from '@/components/charts/AppChoroplethMap';
import { AppMapFilter } from '@/components/charts/AppMapFilter';
import { AppSeo } from '@/components/layout/AppSeo';
import { feedQueryOptions } from '@/hooks/queries/useFlagFeed';
import { useBgProvincesGeo } from '@/hooks/queries/useBgProvincesGeo';
import { useRegionAggregate } from '@/hooks/queries/useRegionAggregate';
import { paths } from '@/routes/paths';
import type { ProcurementSector } from '@/types/api';

/** Corruption-by-region map: oblasti shaded by flag count, filterable by sector; click a region
 *  to expand it and drill into that region's feed. */
export function MapView() {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [category, setCategory] = useState<ProcurementSector | null>(null);
  const geo = useBgProvincesGeo();
  const agg = useRegionAggregate(category);

  // Match the query FeedView builds for `/feed?region=CODE` so the warmed cache is a hit.
  const prefetchRegion = (code: string): void => {
    void queryClient.prefetchInfiniteQuery(
      feedQueryOptions({ sort: 'newest', type: [], category: [], severity: [], region: code }),
    );
  };

  return (
    <Stack spacing={2}>
      <AppSeo title={t('viz:map.seoTitle')} />
      <AppMapFilter value={category} onChange={setCategory} />
      <AppChartFrame
        title={t('viz:map.heading')}
        subtitle={t('viz:map.subtitle')}
        isPending={geo.isPending || agg.isPending}
        isError={geo.isError || agg.isError}
        error={geo.error ?? agg.error}
        onRetry={() => {
          void geo.refetch();
          void agg.refetch();
        }}
        isEmpty={agg.data !== undefined && agg.data.length === 0}
      >
        {geo.data !== undefined && agg.data !== undefined ? (
          <AppChoroplethMap
            geo={geo.data}
            aggregates={agg.data}
            onRegionPrefetch={prefetchRegion}
            onSelectRegion={(code) => navigate(`${paths.feed}?region=${encodeURIComponent(code)}`)}
          />
        ) : null}
      </AppChartFrame>
    </Stack>
  );
}
