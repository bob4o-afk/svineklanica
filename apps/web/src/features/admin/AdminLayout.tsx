import { Divider, Stack, Tab, Tabs, Typography } from '@mui/material';
import { SignOutIcon } from '@phosphor-icons/react';
import { useTranslation } from 'react-i18next';
import { Link as RouterLink, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { AppButton } from '@/components/controls/AppButton';
import { AppSeo } from '@/components/layout/AppSeo';
import { useLogout } from '@/hooks/queries/useAdminAuth';
import { useAuth } from '@/hooks/useAuth';
import { paths } from '@/routes/paths';

const ADMIN_TABS = [
  { value: paths.admin, labelKey: 'admin:nav.dashboard' },
  { value: paths.adminPending, labelKey: 'admin:nav.pending' },
  { value: paths.adminSources, labelKey: 'admin:nav.sources' },
] as const;

/** Which top tab is "active" for the current path — the review detail keeps the queue tab lit. */
function activeTab(pathname: string): string {
  if (pathname.startsWith('/admin/pending') || pathname.startsWith('/admin/review')) {
    return paths.adminPending;
  }
  if (pathname.startsWith('/admin/sources')) return paths.adminSources;
  return paths.admin;
}

/** Shared chrome for every authenticated admin screen: section tabs, the logged-in editor, and
 *  logout. Sits inside ProtectedRoute, so it only ever renders for an authenticated admin. */
export function AdminLayout() {
  const { t } = useTranslation();
  const { user } = useAuth();
  const location = useLocation();
  const navigate = useNavigate();
  const logout = useLogout();

  function onLogout() {
    logout.mutate(undefined, {
      onSuccess: () => navigate(paths.adminLogin, { replace: true }),
    });
  }

  return (
    <Stack spacing={3} sx={{ py: 2 }}>
      <AppSeo title={t('admin:nav.dashboard')} noindex />
      <Stack
        direction={{ xs: 'column', sm: 'row' }}
        alignItems={{ sm: 'center' }}
        justifyContent="space-between"
        spacing={1.5}
      >
        <Tabs value={activeTab(location.pathname)} variant="scrollable" scrollButtons="auto">
          {ADMIN_TABS.map((tab) => (
            <Tab
              key={tab.value}
              value={tab.value}
              label={t(tab.labelKey)}
              component={RouterLink}
              to={tab.value}
            />
          ))}
        </Tabs>
        <Stack direction="row" alignItems="center" spacing={1.5} flexShrink={0}>
          {user !== null ? (
            <Typography variant="caption" color="text.secondary">
              {t('admin:nav.loggedInAs')}: {user.name}
            </Typography>
          ) : null}
          <AppButton
            variant="outlined"
            size="small"
            startIcon={<SignOutIcon />}
            onClick={onLogout}
            disabled={logout.isPending}
          >
            {t('admin:nav.logout')}
          </AppButton>
        </Stack>
      </Stack>
      <Divider />
      <Outlet />
    </Stack>
  );
}
