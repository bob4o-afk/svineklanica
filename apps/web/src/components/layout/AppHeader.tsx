import { AppBar, Stack, Toolbar } from '@mui/material';
import { useTranslation } from 'react-i18next';
import { AppColorModeToggle } from '@/components/controls/AppColorModeToggle';
import { AppLink } from '@/components/controls/AppLink';
import { BRAND } from '@/config/brand';
import { paths } from '@/routes/paths';
import { AppBrandmark } from './AppBrandmark';

/** Sticky top bar: wordmark -> home, primary nav, and the dark/light toggle. */
export function AppHeader() {
  const { t } = useTranslation();

  return (
    <AppBar position="sticky">
      <Toolbar component="nav" sx={{ gap: 2 }}>
        <AppLink to={paths.home} color="inherit" underline="none" aria-label={BRAND.name} sx={{ mr: 'auto' }}>
          <AppBrandmark size="sm" />
        </AppLink>
        <Stack direction="row" spacing={{ xs: 1.5, sm: 2 }} alignItems="center">
          <AppLink to={paths.feed} color="inherit" underline="hover">
            {t('common:nav.feed')}
          </AppLink>
          <AppLink
            to={paths.about}
            color="inherit"
            underline="hover"
            sx={{ display: { xs: 'none', sm: 'inline' } }}
          >
            {t('common:nav.about')}
          </AppLink>
          <AppColorModeToggle />
        </Stack>
      </Toolbar>
    </AppBar>
  );
}
