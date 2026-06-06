import { useTranslation } from 'react-i18next';
import { AppChartFrame } from '@/components/charts/AppChartFrame';
import { AppSerialWinnerGraph } from '@/components/charts/AppSerialWinnerGraph';
import { AppSeo } from '@/components/layout/AppSeo';
import { useSerialWinnerGraph } from '@/hooks/queries/useSerialWinnerGraph';

export interface NetworkViewProps {
  publicId: string;
}

/** Serial-winner network page: the winner↔authority graph for a company. */
export function NetworkView({ publicId }: NetworkViewProps) {
  const { t } = useTranslation();
  const query = useSerialWinnerGraph(publicId);
  const graph = query.data;

  return (
    <>
      <AppSeo title={t('viz:network.seoTitle')} />
      <AppChartFrame
        title={t('viz:network.heading')}
        subtitle={t('viz:network.subtitle')}
        isPending={query.isPending}
        isError={query.isError}
        error={query.error}
        onRetry={() => void query.refetch()}
        isEmpty={graph !== undefined && graph.nodes.length === 0}
      >
        {graph ? <AppSerialWinnerGraph graph={graph} /> : null}
      </AppChartFrame>
    </>
  );
}
