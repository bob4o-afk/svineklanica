import { type ReactNode, useMemo } from 'react';
import { useMe } from '@/hooks/queries/useMe';
import { AuthContext, type AuthContextValue } from './authContext';

/** Provides the session to the tree by reading `GET /api/user` (useMe). A 401 → null, so
 *  anonymous visitors are simply "not admin" with no error. This is UX only — the server stays
 *  the real authority: the public API never returns unapproved data and admin endpoints enforce
 *  the Sanctum session regardless of what this context says (security.md). */
export function AuthProvider({ children }: { children: ReactNode }) {
  const me = useMe();
  const value = useMemo<AuthContextValue>(() => {
    const user = me.data ?? null;
    return { user, isAdmin: user?.isAdmin ?? false, isLoading: me.isPending };
  }, [me.data, me.isPending]);
  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
