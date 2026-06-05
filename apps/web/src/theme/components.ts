import type { Components, Theme } from '@mui/material/styles';
import { radii } from './tokens';

/** The craft layer — MUI component overrides that make the punk palette look intentional. */
export const components: Components<Theme> = {
  MuiCssBaseline: {
    styleOverrides: {
      body: { WebkitFontSmoothing: 'antialiased' },
    },
  },
  MuiButton: {
    defaultProps: { disableElevation: true },
    styleOverrides: { root: { borderRadius: radii.sm, fontWeight: 600 } },
  },
  MuiCard: {
    styleOverrides: {
      root: ({ theme }) => ({
        borderRadius: radii.md,
        border: `1px solid ${theme.palette.divider}`,
        backgroundImage: 'none',
      }),
    },
  },
  MuiAppBar: {
    defaultProps: { elevation: 0, color: 'transparent' },
    styleOverrides: {
      root: ({ theme }) => ({
        borderBottom: `1px solid ${theme.palette.divider}`,
        backdropFilter: 'blur(6px)',
      }),
    },
  },
  MuiChip: {
    styleOverrides: { root: { borderRadius: radii.sm, fontWeight: 600 } },
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
};
