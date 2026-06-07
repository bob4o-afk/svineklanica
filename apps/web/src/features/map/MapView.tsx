import { Stack } from '@mui/material';
import { useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { AppChartFrame } from '@/components/charts/AppChartFrame';
import { AppChoroplethMap } from '@/components/charts/AppChoroplethMap';
import { AppMapFilter } from '@/components/charts/AppMapFilter';
import { AppRegionFlagsSheet } from '@/components/charts/AppRegionFlagsSheet';
import { AppSeo } from '@/components/layout/AppSeo';
import { useFlagMapPoints } from '@/hooks/queries/useFlagMapPoints';
import { feedQueryOptions } from '@/hooks/queries/useFlagFeed';
import { useBgProvincesGeo } from '@/hooks/queries/useBgProvincesGeo';
import { useIsMobile } from '@/hooks/useIsMobile';
import { useRegionAggregate } from '@/hooks/queries/useRegionAggregate';
import { paths } from '@/routes/paths';
import type { ProcurementSector } from '@/types/api';

/** Corruption-by-region map: oblasti shaded by flag count, filterable by sector; click a region
 *  to expand it and drill into that region's feed. */
export function MapView() {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const isMobile = useIsMobile();
  const [category, setCategory] = useState<ProcurementSector | null>(null);
  // On mobile a region tap opens this sheet (the flags there) instead of navigating away.
  const [sheetRegion, setSheetRegion] = useState<string | null>(null);
  const geo = useBgProvincesGeo();
  const agg = useRegionAggregate(category);

  // Every flag that carries a location, pinned at its region centroid (lightweight endpoint, not
  // the heavy feed). Shown for all sectors; the sector filter shades the regions underneath.
  const points = useFlagMapPoints();
  const flagPoints = points.data ?? [];

  // Match the query FeedView builds for `/feed?region=CODE` so the warmed cache is a hit.
  const prefetchRegion = (code: string): void => {
    void queryClient.prefetchInfiniteQuery(
      feedQueryOptions({ sort: 'newest', type: [], category: [], severity: [], region: code }),
    );
  };

  const goToRegionFeed = (code: string): void =>
    void navigate(`${paths.feed}?region=${encodeURIComponent(code)}`);

  // Desktop: drill straight into the region's feed. Mobile (no hover): open the bottom sheet
  // listing the flags pinned there — then a tap drills into a specific post.
  const handleSelectRegion = (code: string): void => {
    if (isMobile) {
      setSheetRegion(code);
    } else {
      goToRegionFeed(code);
    }
  };

  return (
    <Stack spacing={2}>
      <AppSeo
        title={t('viz:map.seoTitle')}
        description={t('viz:map.subtitle')}
        keywords={['карта на корупцията', 'обществени поръчки по области', 'корупция по региони']}
      />
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
            flagPoints={flagPoints}
            onSelectFlag={(publicId) => navigate(paths.post(publicId))}
            onRegionPrefetch={prefetchRegion}
            onSelectRegion={handleSelectRegion}
            animateRegionSelect={!isMobile}
          />
        ) : null}
      </AppChartFrame>

      <AppRegionFlagsSheet
        regionCode={sheetRegion}
        onClose={() => setSheetRegion(null)}
        flags={flagPoints}
        onSelectFlag={(publicId) => navigate(paths.post(publicId))}
        onViewFeed={goToRegionFeed}
      />
    </Stack>
  );
}
