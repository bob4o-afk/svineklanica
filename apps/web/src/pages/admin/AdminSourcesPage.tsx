import { AdminSourcesView } from '@/features/admin/AdminSourcesView';
import { useRenderLog } from '@/hooks/useRenderLog';

export function AdminSourcesPage() {
  useRenderLog('AdminSourcesPage');
  return <AdminSourcesView />;
}
