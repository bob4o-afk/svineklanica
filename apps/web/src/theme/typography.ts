import type { ThemeOptions } from '@mui/material/styles';

/** JetBrains Mono for the punk edge (wordmark/headings/numbers); Inter for readable
 *  Bulgarian body text. Both self-hosted via @fontsource (CSP/offline-friendly). */
export const fonts = {
  mono: '"JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, monospace',
  sans: '"Inter", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
} as const;

export const typography: NonNullable<ThemeOptions['typography']> = {
  fontFamily: fonts.sans,
  h1: { fontFamily: fonts.mono, fontWeight: 700, letterSpacing: '-0.02em' },
  h2: { fontFamily: fonts.mono, fontWeight: 700, letterSpacing: '-0.01em' },
  h3: { fontFamily: fonts.mono, fontWeight: 700 },
  h4: { fontFamily: fonts.mono, fontWeight: 700 },
  h5: { fontFamily: fonts.mono, fontWeight: 600 },
  h6: { fontFamily: fonts.mono, fontWeight: 600 },
  overline: { fontFamily: fonts.mono, fontWeight: 600, letterSpacing: '0.08em' },
  button: { textTransform: 'none', fontWeight: 600 },
};
