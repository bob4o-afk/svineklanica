import { ComingSoonView } from '@/features/placeholder/ComingSoonView';
import { useRenderLog } from '@/hooks/useRenderLog';

/** Admin stub — login, pending queue, review panel, and sources land in Phase 4.
 *  All admin routes share this until the real Sanctum-cookie flow exists. Always noindex. */
export function AdminPlaceholderPage() {
  useRenderLog('AdminPlaceholderPage');
  return <ComingSoonView titleKey="common:nav.admin" noindex />;
}
