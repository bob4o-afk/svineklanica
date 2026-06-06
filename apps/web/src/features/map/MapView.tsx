import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { AppChartFrame } from '@/components/charts/AppChartFrame';
import { AppChoroplethMap } from '@/components/charts/AppChoroplethMap';
import { AppSeo } from '@/components/layout/AppSeo';
import { useBgProvincesGeo } from '@/hooks/queries/useBgProvincesGeo';
import { useRegionAggregate } from '@/hooks/queries/useRegionAggregate';
import { paths } from '@/routes/paths';

/** Corruption-by-region map: oblasti shaded by flag count; click a region to see its feed. */
export function MapView() {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const geo = useBgProvincesGeo();
  const agg = useRegionAggregate();

  return (
    <>
      <AppSeo title={t('viz:map.seoTitle')} />
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
            onSelectRegion={(code) => navigate(`${paths.feed}?region=${encodeURIComponent(code)}`)}
          />
        ) : null}
      </AppChartFrame>
    </>
  );
}
