import type { Components, Theme } from '@mui/material/styles';
import { palette, radii } from './tokens';

/** The craft layer — MUI component overrides that make the punk palette look intentional. */
export const components: Components<Theme> = {
  MuiCssBaseline: {
    styleOverrides: {
      body: { WebkitFontSmoothing: 'antialiased' },
    },
  },
  MuiButton: {
    defaultProps: { disableElevation: true },
    styleOverrides: {
      root: {
        borderRadius: 0,
        fontWeight: 700,
        letterSpacing: '0.04em',
        transition: 'box-shadow 0.12s ease, transform 0.12s ease',
        '&:hover': {
          boxShadow: `3px 3px 0 ${palette.alarm}`,
          transform: 'translate(-1px, -1px)',
        },
      },
      containedPrimary: {
        '&:hover': {
          boxShadow: `3px 3px 0 ${palette.bone}`,
          transform: 'translate(-1px, -1px)',
        },
      },
    },
  },
  MuiCard: {
    styleOverrides: {
      root: ({ theme }) => ({
        borderRadius: radii.sm,
        border: `1px solid ${theme.palette.divider}`,
        backgroundImage: 'none',
        transition: 'box-shadow 0.12s ease, transform 0.12s ease, border-color 0.12s ease',
        '&.punk-card:hover': {
          boxShadow: `4px 4px 0 ${palette.alarm}`,
          transform: 'translate(-2px, -2px)',
          borderColor: palette.alarm,
        },
      }),
    },
  },
  MuiAppBar: {
    defaultProps: { elevation: 0, color: 'transparent' },
    styleOverrides: {
      root: ({ theme }) => ({
        borderBottom: `1px solid ${theme.palette.divider}`,
        backdropFilter: 'blur(8px)',
        WebkitBackdropFilter: 'blur(8px)',
        // Solid enough that the watermark never bleeds through at the header edges.
        // Using 0.97 opacity preserves the blur while fully masking the background layer.
        backgroundColor:
          theme.palette.mode === 'dark'
            ? 'rgba(17, 17, 17, 1)'
            : 'rgba(244, 241, 234, 1)',
      }),
    },
  },
  MuiChip: {
    styleOverrides: {
      root: {
        borderRadius: 0,
        fontWeight: 600,
        letterSpacing: '0.04em',
      },
    },
  },
  MuiLink: {
    // Links are always underlined for affordance. Default (primary) links take their color from
    // the mode-scoped accent (primary.main: ink in light, acid in dark — see theme/tokens.ts);
    // we force the underline to the full accent color so it stays crisp instead of MUI's faded
    // default. (frontend.md §1)
    defaultProps: { underline: 'always' },
    styleOverrides: {
      root: ({ theme, ownerState }) =>
        ownerState.color === 'primary'
          ? { textDecorationColor: theme.palette.primary.main }
          : {},
    },
  },
  MuiDivider: {
    styleOverrides: {
      root: { borderColor: 'rgba(244, 241, 234, 0.08)' },
    },
  },
  MuiTooltip: {
    styleOverrides: {
      tooltip: {
        borderRadius: 0,
        fontWeight: 600,
        letterSpacing: '0.04em',
        fontSize: '0.75rem',
      },
    },
  },
};
