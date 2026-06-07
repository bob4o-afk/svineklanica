import { Outlet } from 'react-router-dom';
import { AuthProvider } from '@/providers/AuthProvider';

/** Wraps the admin route subtree (login + the protected console) in AuthProvider, so the session
 *  probe (`GET /api/admin/me`) fires ONLY when a user is in the admin area — never on the public
 *  pages. That matters because `/api/admin/*` is IP-allow-list-gated (security.md §4): a public
 *  visitor probing it from a non-whitelisted IP would be auto-blacklisted. Public pages don't
 *  consume the session anyway, so they never mount this. */
export function AdminSection() {
  return (
    <AuthProvider>
      <Outlet />
    </AuthProvider>
  );
}
