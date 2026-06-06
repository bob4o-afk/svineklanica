import { type ReactNode, useMemo } from 'react';
import { AuthContext, type AuthContextValue } from './authContext';

/** Phase 1 stub: nobody is authenticated. The real Sanctum SPA-cookie flow
 *  (GET /sanctum/csrf-cookie -> POST /login -> GET /api/admin/me) lands in Phase 4. */
export function AuthProvider({ children }: { children: ReactNode }) {
  const value = useMemo<AuthContextValue>(
    () => ({ user: null, isAdmin: false, isLoading: false }),
    [],
  );
  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
