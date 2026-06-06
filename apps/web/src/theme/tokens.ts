/** Design tokens — mirror of `tailwind.config.ts`. The ONE source for colors/radii.
 *  No hex/magic values anywhere else (frontend.md §1). */

export const palette = {
  ink: '#0a0a0a',
  bone: '#f4f1ea',
  alarm: '#ff2d2d',
  acid: '#a8cc00', // dimmed lime — same hue as #c6ff00, 20% less neon
  rust: '#b5651d',
  muted: '#6b7280',
} as const;

/** Barely-there rounding — visually sharp, not fully square. */
export const radii = { sm: 2, md: 2, lg: 4 } as const;

/** The accent (MUI `primary`) is mode-scoped: acid lime only survives on the dark background
 *  where it has contrast. In light mode the accent is ink — lime is removed entirely (it was
 *  near-invisible on the cream bg). `onAccent` is the readable text laid over the accent. */
export const darkColors = {
  bg: '#111111',      // not void-black — surfaces need visible depth
  paper: '#1c1c1e',   // card/paper lift above bg
  text: palette.bone,
  divider: 'rgba(244, 241, 234, 0.1)',
  accent: palette.acid,
  onAccent: palette.ink,
} as const;

export const lightColors = {
  bg: palette.bone,
  paper: '#ffffff',
  text: palette.ink,
  divider: 'rgba(10, 10, 10, 0.12)',
  accent: palette.ink,
  onAccent: palette.bone,
} as const;
