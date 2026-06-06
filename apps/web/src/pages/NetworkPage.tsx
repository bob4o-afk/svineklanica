import { useParams } from 'react-router-dom';
import { NetworkView } from '@/features/network/NetworkView';
import { useRenderLog } from '@/hooks/useRenderLog';
import { NotFoundPage } from './NotFoundPage';

/** Serial-winner network graph for a company public id. */
export function NetworkPage() {
  useRenderLog('NetworkPage');
  const { publicId } = useParams();
  if (publicId === undefined || publicId === '') return <NotFoundPage />;
  return <NetworkView publicId={publicId} />;
}
