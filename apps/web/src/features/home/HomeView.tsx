import { Box, Stack, Typography } from '@mui/material';
import { ArrowRightIcon } from '@phosphor-icons/react';
import { useTranslation } from 'react-i18next';
import { AppButton } from '@/components/controls/AppButton';
import { AppLink } from '@/components/controls/AppLink';
import { AppBrandmark } from '@/components/layout/AppBrandmark';
import { FeedList } from '@/features/feed/FeedList';
import { paths } from '@/routes/paths';

/** Landing page: punk wordmark hero + one-line manifesto + a teaser of the latest flags. */
export function HomeView() {
  const { t } = useTranslation();

  return (
    <Stack spacing={6}>
      <Stack spacing={2} sx={{ pt: { xs: 2, sm: 4 } }}>
        <Typography variant="overline" color="text.secondary">
          {t('home:hero.kicker')}
        </Typography>
        <AppBrandmark size="lg" showTagline />
        <Typography variant="h3" component="h1" sx={{ maxWidth: 720 }}>
          {t('home:hero.title')}
        </Typography>
        <Typography variant="body1" color="text.secondary" sx={{ maxWidth: 640 }}>
          {t('home:hero.subtitle')}
        </Typography>
        <Box>
          <AppButton to={paths.feed} endIcon={<ArrowRightIcon />}>
            {t('home:hero.cta')}
          </AppButton>
        </Box>
      </Stack>

      <Stack spacing={2}>
        <Stack direction="row" justifyContent="space-between" alignItems="baseline">
          <Typography variant="h5" component="h2">
            {t('home:latest.title')}
          </Typography>
          <AppLink to={paths.feed}>{t('home:latest.all')}</AppLink>
        </Stack>
        <FeedList query={{ sort: 'newest' }} limit={3} />
      </Stack>
    </Stack>
  );
}
