import { AdminLoginView } from '@/features/admin/AdminLoginView';
import { useRenderLog } from '@/hooks/useRenderLog';

export function AdminLoginPage() {
  useRenderLog('AdminLoginPage');
  return <AdminLoginView />;
}
