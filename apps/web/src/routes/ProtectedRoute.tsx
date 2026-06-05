import { Navigate, Outlet } from 'react-router-dom';
import { AppSkeleton } from '@/components/feedback/AppSkeleton';
import { useAuth } from '@/hooks/useAuth';
import { paths } from '@/routes/paths';

/** Client-side gate for admin routes. UX ONLY — the server is the real authority (security.md):
 *  the public API never returns unapproved data, and admin endpoints enforce the Sanctum session.
 *  Phase 1: AuthProvider is a stub (never admin), so this always redirects to the login stub. */
export function ProtectedRoute() {
  const { isAdmin, isLoading } = useAuth();

  if (isLoading) return <AppSkeleton count={2} />;
  if (!isAdmin) return <Navigate to={paths.adminLogin} replace />;
  return <Outlet />;
}
