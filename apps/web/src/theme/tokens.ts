/** Design tokens — mirror of `tailwind.config.ts`. The ONE source for colors/radii.
 *  No hex/magic values anywhere else (frontend.md §1). */

export const palette = {
  ink: '#0a0a0a',
  bone: '#f4f1ea',
  alarm: '#ff2d2d',
  acid: '#c6ff00',
  rust: '#b5651d',
  muted: '#6b7280',
} as const;

/** Sharp, zine-ish corners. */
export const radii = { sm: 2, md: 4, lg: 8 } as const;

export const darkColors = {
  bg: palette.ink,
  paper: '#141414',
  text: palette.bone,
  divider: 'rgba(244, 241, 234, 0.12)',
} as const;

export const lightColors = {
  bg: palette.bone,
  paper: '#ffffff',
  text: palette.ink,
  divider: 'rgba(10, 10, 10, 0.12)',
} as const;
