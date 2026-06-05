import { AppBar, Menu, MenuItem, Stack, Toolbar } from '@mui/material';
import { ListIcon } from '@phosphor-icons/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link as RouterLink } from 'react-router-dom';
import { AppColorModeToggle } from '@/components/controls/AppColorModeToggle';
import { AppIconButton } from '@/components/controls/AppIconButton';
import { AppLink } from '@/components/controls/AppLink';
import { BRAND } from '@/config/brand';
import { paths } from '@/routes/paths';
import { AppBrandmark } from './AppBrandmark';

/** Primary navigation, declared once and rendered both inline (>= sm) and inside the phone menu
 *  so a link can never be reachable in one form factor but not the other. */
const NAV_ITEMS: ReadonlyArray<{ to: string; labelKey: string }> = [
  { to: paths.feed, labelKey: 'common:nav.feed' },
  { to: paths.about, labelKey: 'common:nav.about' },
];

/** Sticky top bar: wordmark -> home, the primary nav (inline on >= sm, a menu on phones), and the
 *  dark/light toggle. */
export function AppHeader() {
  const { t } = useTranslation();
  const [anchorEl, setAnchorEl] = useState<HTMLElement | null>(null);
  const menuOpen = anchorEl !== null;
  const closeMenu = () => setAnchorEl(null);

  return (
    <AppBar position="sticky">
      <Toolbar component="nav" sx={{ gap: 2 }}>
        <AppLink to={paths.home} color="inherit" underline="none" aria-label={BRAND.name} sx={{ mr: 'auto' }}>
          <AppBrandmark size="sm" />
        </AppLink>

        <Stack direction="row" spacing={2} alignItems="center" sx={{ display: { xs: 'none', sm: 'flex' } }}>
          {NAV_ITEMS.map((item) => (
            <AppLink key={item.to} to={item.to} color="inherit" underline="hover">
              {t(item.labelKey)}
            </AppLink>
          ))}
        </Stack>

        <AppColorModeToggle />

        <AppIconButton
          label={t('common:nav.menu')}
          color="inherit"
          onClick={(event) => setAnchorEl(event.currentTarget)}
          aria-haspopup="menu"
          aria-expanded={menuOpen}
          {...(menuOpen ? { 'aria-controls': 'app-nav-menu' } : {})}
          sx={{ display: { xs: 'inline-flex', sm: 'none' } }}
        >
          <ListIcon size={20} />
        </AppIconButton>
        <Menu id="app-nav-menu" anchorEl={anchorEl} open={menuOpen} onClose={closeMenu}>
          {NAV_ITEMS.map((item) => (
            <MenuItem key={item.to} component={RouterLink} to={item.to} onClick={closeMenu}>
              {t(item.labelKey)}
            </MenuItem>
          ))}
        </Menu>
      </Toolbar>
    </AppBar>
  );
}
