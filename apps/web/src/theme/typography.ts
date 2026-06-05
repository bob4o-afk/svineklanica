import type { ThemeOptions } from '@mui/material/styles';

/** JetBrains Mono for the punk edge (wordmark/headings/numbers); Inter for readable
 *  Bulgarian body text. Both self-hosted via @fontsource (CSP/offline-friendly). */
export const fonts = {
  mono: '"JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, monospace',
  sans: '"Inter", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
} as const;

export const typography: NonNullable<ThemeOptions['typography']> = {
  fontFamily: fonts.sans,
  // Long Bulgarian words (e.g. "Прозрачността") must break rather than overflow the viewport
  // at phone widths — break only when a word can't fit (overflow-wrap, not word-break).
  h1: { fontFamily: fonts.mono, fontWeight: 700, letterSpacing: '-0.02em', overflowWrap: 'break-word' },
  h2: { fontFamily: fonts.mono, fontWeight: 700, letterSpacing: '-0.01em', overflowWrap: 'break-word' },
  h3: { fontFamily: fonts.mono, fontWeight: 700, overflowWrap: 'break-word' },
  h4: { fontFamily: fonts.mono, fontWeight: 700, overflowWrap: 'break-word' },
  h5: { fontFamily: fonts.mono, fontWeight: 600, overflowWrap: 'break-word' },
  h6: { fontFamily: fonts.mono, fontWeight: 600, overflowWrap: 'break-word' },
  overline: { fontFamily: fonts.mono, fontWeight: 600, letterSpacing: '0.08em' },
  button: { textTransform: 'none', fontWeight: 600 },
};
