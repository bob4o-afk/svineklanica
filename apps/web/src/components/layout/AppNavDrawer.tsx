import { Box, Grow, Modal, Stack, Typography } from '@mui/material';
import { XIcon } from '@phosphor-icons/react';
import { useTranslation } from 'react-i18next';
import { Link as RouterLink, useLocation } from 'react-router-dom';
import { AppIconButton } from '@/components/controls/AppIconButton';
import { BRAND } from '@/config/brand';
import { fonts } from '@/theme/typography';
import { palette } from '@/theme/tokens';

export interface AppNavItem {
  to: string;
  labelKey: string;
}

export interface AppNavDrawerProps {
  open: boolean;
  onClose: () => void;
  items: ReadonlyArray<AppNavItem>;
}

/** Mobile navigation panel. Instead of a "flying box", it scales out of the top-right corner
 *  (where the hamburger lives) over a dimmed/blurred backdrop, with the rows staggering in —
 *  so it reads as connected to the button, like a proper sidebar. Modal gives us the portal,
 *  focus trap, Esc-to-close and scroll lock for free (a11y). Phone-only — the header decides
 *  via breakpoints (frontend.md §0). */
export function AppNavDrawer({ open, onClose, items }: AppNavDrawerProps) {
  const { t } = useTranslation();
  const { pathname } = useLocation();

  return (
    <Modal
      open={open}
      onClose={onClose}
      closeAfterTransition
      aria-label={t('common:nav.menu')}
      slotProps={{
        backdrop: {
          timeout: 260,
          sx: {
            backgroundColor: 'rgba(0, 0, 0, 0.6)',
            backdropFilter: 'blur(4px)',
            WebkitBackdropFilter: 'blur(4px)',
          },
        },
      }}
    >
      {/* Origin top-right = grows out of the hamburger button, not from nowhere. */}
      <Grow in={open} style={{ transformOrigin: 'top right' }} timeout={260}>
        <Box
          sx={(theme) => ({
            position: 'fixed',
            top: 8,
            right: 8,
            width: 'min(82vw, 320px)',
            maxHeight: 'calc(100svh - 16px)',
            overflowY: 'auto',
            bgcolor: 'background.paper',
            border: `1px solid ${theme.palette.divider}`,
            borderRadius: '4px',
            boxShadow: `8px 8px 0 ${palette.alarm}`,
            outline: 'none',
            p: 1.5,
          })}
        >
          <Stack
            direction="row"
            alignItems="center"
            justifyContent="space-between"
            sx={{ mb: 1, pl: 1 }}
          >
            <Typography
              sx={{
                fontFamily: fonts.mono,
                fontWeight: 700,
                fontSize: '0.7rem',
                letterSpacing: '0.16em',
                textTransform: 'uppercase',
                color: 'text.secondary',
              }}
            >
              {BRAND.name}
            </Typography>
            <AppIconButton label={t('common:actions.close')} color="inherit" onClick={onClose} edge="end">
              <XIcon size={20} />
            </AppIconButton>
          </Stack>

          <Stack component="nav" spacing={0.5}>
            {items.map((item, index) => {
              const active = pathname === item.to;
              return (
                <Grow
                  key={item.to}
                  in={open}
                  style={{ transformOrigin: 'top right', transitionDelay: open ? `${80 + index * 45}ms` : '0ms' }}
                  timeout={260}
                >
                  <Box
                    component={RouterLink}
                    to={item.to}
                    onClick={onClose}
                    sx={(theme) => ({
                      display: 'block',
                      px: 2,
                      py: 1.75,
                      borderRadius: '2px',
                      textDecoration: 'none',
                      fontFamily: fonts.mono,
                      fontWeight: 700,
                      fontSize: '1.05rem',
                      letterSpacing: '0.04em',
                      textTransform: 'uppercase',
                      color: active ? palette.alarm : theme.palette.text.primary,
                      borderLeft: `3px solid ${active ? palette.alarm : 'transparent'}`,
                      transition: 'background-color 0.12s ease, border-color 0.12s ease, color 0.12s ease',
                      '&:hover': {
                        backgroundColor: theme.palette.action.hover,
                        borderLeftColor: palette.alarm,
                      },
                    })}
                  >
                    {t(item.labelKey)}
                  </Box>
                </Grow>
              );
            })}
          </Stack>
        </Box>
      </Grow>
    </Modal>
  );
}
