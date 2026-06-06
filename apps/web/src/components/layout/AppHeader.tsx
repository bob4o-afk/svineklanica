import { AppBar, Box, Fade, Menu, MenuItem, Stack, Toolbar, Typography, useScrollTrigger } from '@mui/material';
import { ListIcon, MagnifyingGlassIcon } from '@phosphor-icons/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link as RouterLink, useLocation, useNavigate } from 'react-router-dom';
import { AppColorModeToggle } from '@/components/controls/AppColorModeToggle';
import { AppIconButton } from '@/components/controls/AppIconButton';
import { AppLink } from '@/components/controls/AppLink';
import { BRAND } from '@/config/brand';
import { paths } from '@/routes/paths';
import { fonts } from '@/theme/typography';
import { palette } from '@/theme/tokens';
import { AppBrandmark } from './AppBrandmark';

const NAV_ITEMS: ReadonlyArray<{ to: string; labelKey: string }> = [
  { to: paths.feed, labelKey: 'common:nav.feed' },
  { to: paths.map, labelKey: 'common:nav.map' },
  { to: paths.about, labelKey: 'common:nav.about' },
];

/** Sticky top bar. The logo/name is hidden at the top of the page and fades in once the
 *  user has scrolled past the hero — so the hero itself acts as the branding and the
 *  header stays clean until you need an anchor back home. */
export function AppHeader() {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const { pathname } = useLocation();
  const [anchorEl, setAnchorEl] = useState<HTMLElement | null>(null);
  const menuOpen = anchorEl !== null;
  const closeMenu = () => setAnchorEl(null);

  const isHome = pathname === paths.home;
  // On the homepage the hero IS the branding, so the logo only appears once you scroll past it
  // (~280px). On every other page the logo + name stay visible at all times.
  const scrolled = useScrollTrigger({ threshold: 280, disableHysteresis: true });
  const logoVisible = !isHome || scrolled;

  return (
    <AppBar position="sticky">
      <Toolbar component="nav" sx={{ gap: 2 }}>
        {/* Logo — on the homepage it fades in once the hero scrolls off; on inner pages it is
            always visible. Always rendered so the flex layout stays stable. */}
        <Box sx={{ mr: 'auto', display: 'flex', alignItems: 'center' }}>
          <Fade in={logoVisible} timeout={250}>
            <AppLink
              to={paths.home}
              color="inherit"
              underline="none"
              aria-label={BRAND.name}
              tabIndex={logoVisible ? 0 : -1}
              aria-hidden={!logoVisible}
            >
              <AppBrandmark height={36} />
            </AppLink>
          </Fade>
        </Box>

        {/* Live indicator */}
        <Stack
          direction="row"
          spacing={0.75}
          alignItems="center"
          sx={{ display: { xs: 'none', md: 'flex' }, mr: 1 }}
        >
          <Box className="punk-live-dot" aria-hidden />
          <Typography
            component="span"
            sx={{
              fontFamily: fonts.mono,
              fontWeight: 600,
              fontSize: '0.65rem',
              letterSpacing: '0.12em',
              textTransform: 'uppercase',
              color: palette.alarm,
            }}
          >
            {t('common:live')}
          </Typography>
        </Stack>

        <Stack
          direction="row"
          spacing={2.5}
          alignItems="center"
          sx={{ display: { xs: 'none', sm: 'flex' } }}
        >
          {NAV_ITEMS.map((item) => (
            <AppLink
              key={item.to}
              to={item.to}
              color="inherit"
              underline="hover"
              sx={{
                fontFamily: fonts.mono,
                fontWeight: 700,
                fontSize: '0.82rem',
                letterSpacing: '0.06em',
                textTransform: 'uppercase',
              }}
            >
              {t(item.labelKey)}
            </AppLink>
          ))}
        </Stack>

        <AppIconButton label={t('search:open')} color="inherit" onClick={() => navigate(paths.search)}>
          <MagnifyingGlassIcon size={20} />
        </AppIconButton>

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
