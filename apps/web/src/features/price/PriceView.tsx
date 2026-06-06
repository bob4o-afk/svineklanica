import { useTranslation } from 'react-i18next';
import { AppChartFrame } from '@/components/charts/AppChartFrame';
import { AppPriceChart } from '@/components/charts/AppPriceChart';
import { AppSeo } from '@/components/layout/AppSeo';
import { usePriceSeries } from '@/hooks/queries/usePriceSeries';
import type { SourceRef } from '@/types/api';

function uniqueSources(sources: SourceRef[]): SourceRef[] {
  const seen = new Set<string>();
  return sources.filter((s) => {
    if (seen.has(s.url)) return false;
    seen.add(s.url);
    return true;
  });
}

export interface PriceViewProps {
  seriesKey: string;
}

/** Price-over-time page: one product's unit price across procurement captures, with the
 *  primary source behind every point. */
export function PriceView({ seriesKey }: PriceViewProps) {
  const { t } = useTranslation();
  const query = usePriceSeries(seriesKey);
  const series = query.data;
  const sources = series ? uniqueSources(series.points.map((p) => p.source)) : [];

  return (
    <>
      <AppSeo
        title={series ? t('viz:price.seoTitle', { product: series.product_label }) : t('viz:price.heading')}
      />
      <AppChartFrame
        title={t('viz:price.heading')}
        {...(series ? { subtitle: series.product_label } : {})}
        isPending={query.isPending}
        isError={query.isError}
        error={query.error}
        onRetry={() => void query.refetch()}
        isEmpty={series !== undefined && series.points.length === 0}
        sources={sources}
      >
        {series ? <AppPriceChart series={series} /> : null}
      </AppChartFrame>
    </>
  );
}
