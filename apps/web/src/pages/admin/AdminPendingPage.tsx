import { AdminPendingView } from '@/features/admin/AdminPendingView';
import { useRenderLog } from '@/hooks/useRenderLog';

export function AdminPendingPage() {
  useRenderLog('AdminPendingPage');
  return <AdminPendingView />;
}
