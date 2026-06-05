import { Stack, Typography } from '@mui/material';
import { useTranslation } from 'react-i18next';
import { AppButton } from '@/components/controls/AppButton';
import { AppSeo } from '@/components/layout/AppSeo';
import { useRenderLog } from '@/hooks/useRenderLog';
import { paths } from '@/routes/paths';

export function NotFoundPage() {
  useRenderLog('NotFoundPage');
  const { t } = useTranslation();

  return (
    <Stack spacing={2} alignItems="center" textAlign="center" sx={{ py: 8 }}>
      <AppSeo title={t('common:notFound.title')} noindex />
      <Typography variant="h2" component="p">
        404
      </Typography>
      <Typography variant="h6" component="h1">
        {t('common:notFound.title')}
      </Typography>
      <Typography variant="body2" color="text.secondary">
        {t('common:notFound.body')}
      </Typography>
      <AppButton to={paths.home}>{t('common:notFound.home')}</AppButton>
    </Stack>
  );
}
