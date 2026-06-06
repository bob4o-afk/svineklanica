import { AdminDashboardView } from '@/features/admin/AdminDashboardView';
import { useRenderLog } from '@/hooks/useRenderLog';

export function AdminDashboardPage() {
  useRenderLog('AdminDashboardPage');
  return <AdminDashboardView />;
}
