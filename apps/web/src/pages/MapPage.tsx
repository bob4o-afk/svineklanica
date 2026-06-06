import { MapView } from '@/features/map/MapView';
import { useRenderLog } from '@/hooks/useRenderLog';

/** Regional corruption choropleth. */
export function MapPage() {
  useRenderLog('MapPage');
  return <MapView />;
}
