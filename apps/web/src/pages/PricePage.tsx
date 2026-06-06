import { useParams } from 'react-router-dom';
import { PriceView } from '@/features/price/PriceView';
import { useRenderLog } from '@/hooks/useRenderLog';
import { NotFoundPage } from './NotFoundPage';

/** Price-over-time chart for a product/category series key. */
export function PricePage() {
  useRenderLog('PricePage');
  const { seriesKey } = useParams();
  if (seriesKey === undefined || seriesKey === '') return <NotFoundPage />;
  return <PriceView seriesKey={seriesKey} />;
}
