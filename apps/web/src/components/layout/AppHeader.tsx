import { AppBar, Box, Fade, Menu, MenuItem, Stack, Toolbar, Typography, useScrollTrigger } from '@mui/material';
import { ListIcon, MagnifyingGlassIcon } from '@phosphor-icons/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link as RouterLink, useNavigate } from 'react-router-dom';
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
  { to: paths.about, labelKey: 'common:nav.about' },
];

/** Sticky top bar. The logo/name is hidden at the top of the page and fades in once the
 *  user has scrolled past the hero — so the hero itself acts as the branding and the
 *  header stays clean until you need an anchor back home. */
export function AppHeader() {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const [anchorEl, setAnchorEl] = useState<HTMLElement | null>(null);
  const menuOpen = anchorEl !== null;
  const closeMenu = () => setAnchorEl(null);

  // Show the logo once the hero has scrolled off screen (~280px covers logo + name).
  const scrolled = useScrollTrigger({ threshold: 280, disableHysteresis: true });

  return (
    <AppBar position="sticky">
      <Toolbar component="nav" sx={{ gap: 2 }}>
        {/* Logo — invisible at the top, fades in on scroll. Always rendered so flex layout is stable. */}
        <Box sx={{ mr: 'auto', display: 'flex', alignItems: 'center' }}>
          <Fade in={scrolled} timeout={250}>
            <AppLink
              to={paths.home}
              color="inherit"
              underline="none"
              aria-label={BRAND.name}
              tabIndex={scrolled ? 0 : -1}
              aria-hidden={!scrolled}
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
                fontWeight: 600,
                fontSize: '0.78rem',
                letterSpacing: '0.08em',
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
