import type { ThemeOptions } from '@mui/material/styles';

/** Three-voice type system:
 *  - display (Manrope 800): headings, wordmark, hero — bold with full Cyrillic
 *  - mono (JetBrains Mono): data labels, meta, nav links, overlines — technical layer
 *  - sans (Inter): body text — readable at length
 *  All self-hosted via @fontsource (CSP/offline-friendly). */
export const fonts = {
  display: '"Manrope", system-ui, -apple-system, sans-serif',
  mono: '"JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, monospace',
  sans: '"Inter", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
} as const;

export const typography: NonNullable<ThemeOptions['typography']> = {
  fontFamily: fonts.sans,
  // Long Bulgarian words must break rather than overflow at phone widths.
  h1: { fontFamily: fonts.display, fontWeight: 800, letterSpacing: '-0.02em', overflowWrap: 'break-word' },
  h2: { fontFamily: fonts.display, fontWeight: 800, letterSpacing: '-0.02em', overflowWrap: 'break-word' },
  h3: { fontFamily: fonts.display, fontWeight: 700, letterSpacing: '-0.01em', overflowWrap: 'break-word' },
  h4: { fontFamily: fonts.display, fontWeight: 700, overflowWrap: 'break-word' },
  h5: { fontFamily: fonts.display, fontWeight: 700, overflowWrap: 'break-word' },
  h6: { fontFamily: fonts.display, fontWeight: 700, overflowWrap: 'break-word' },
  overline: { fontFamily: fonts.mono, fontWeight: 600, letterSpacing: '0.12em' },
  button: { textTransform: 'none', fontWeight: 600 },
};
