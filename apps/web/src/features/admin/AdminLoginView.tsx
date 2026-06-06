import { Alert, Stack, Typography } from '@mui/material';
import { SignInIcon } from '@phosphor-icons/react';
import { type FormEvent, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Navigate, useNavigate } from 'react-router-dom';
import { AppButton } from '@/components/controls/AppButton';
import { AppTextField } from '@/components/controls/AppTextField';
import { AppSeo } from '@/components/layout/AppSeo';
import { useLogin } from '@/hooks/queries/useAdminAuth';
import { useAuth } from '@/hooks/useAuth';
import { paths } from '@/routes/paths';

/** Editor login. Posts to the Sanctum SPA flow (useLogin); on success the session query refreshes
 *  and we navigate into the admin area. Always noindex — this is back-office, never citizen-facing. */
export function AdminLoginView() {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const { isAdmin } = useAuth();
  const login = useLogin();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');

  if (isAdmin) return <Navigate to={paths.admin} replace />;

  function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    login.mutate(
      { email, password },
      { onSuccess: () => navigate(paths.admin, { replace: true }) },
    );
  }

  return (
    <Stack
      component="form"
      onSubmit={onSubmit}
      spacing={2.5}
      sx={{ maxWidth: 420, mx: 'auto', py: 5, width: '100%' }}
    >
      <AppSeo title={t('admin:login.title')} noindex />
      <Typography variant="h4" component="h1">
        {t('admin:login.title')}
      </Typography>
      <Typography variant="body2" color="text.secondary">
        {t('admin:login.subtitle')}
      </Typography>

      {login.isError ? <Alert severity="error">{t('admin:login.error')}</Alert> : null}

      <AppTextField
        id="admin-email"
        label={t('admin:login.email')}
        type="email"
        value={email}
        onChange={(event) => setEmail(event.target.value)}
        autoComplete="username"
        required
      />
      <AppTextField
        id="admin-password"
        label={t('admin:login.password')}
        type="password"
        value={password}
        onChange={(event) => setPassword(event.target.value)}
        autoComplete="current-password"
        required
      />

      <AppButton type="submit" startIcon={<SignInIcon />} disabled={login.isPending}>
        {t('admin:login.submit')}
      </AppButton>

      <Typography variant="caption" color="text.secondary">
        {t('admin:login.demoHint')}
      </Typography>
    </Stack>
  );
}
