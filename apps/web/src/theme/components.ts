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
    defaultProps: { underline: 'hover' },
  },
};
