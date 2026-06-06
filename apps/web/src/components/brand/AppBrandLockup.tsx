import { Box, Stack, Typography } from '@mui/material';
import type { ElementType } from 'react';
import { BRAND } from '@/config/brand';
import { useColorMode } from '@/hooks/useColorMode';
import { fonts } from '@/theme/typography';
import { palette } from '@/theme/tokens';
import blackFullRed from '@/assets/logos/black_full_red.svg';
import whiteFullRed from '@/assets/logos/white_full_red.svg';

export type BrandLockupSize = 'hero' | 'compact';

export interface AppBrandLockupProps {
  /** 'hero' = landing splash (default); 'compact' = header/nav-sized. Both scale down on mobile. */
  size?: BrandLockupSize;
  /** Render the tagline under the name. Default true. */
  showTagline?: boolean;
  /** Semantic element for the name (e.g. 'h1' on the hero). Default 'span'. */
  nameComponent?: ElementType;
}

/** Per-breakpoint sizing tokens — `xs` is the phone size, growing on wider screens. */
const SIZES = {
  hero: {
    logo: { xs: 160, sm: 240, md: 300 },
    name: { xs: '2.25rem', sm: '4rem', md: '5.5rem' },
    tagline: { xs: '0.65rem', sm: '0.8rem' },
    gap: { xs: 2, sm: 3 },
  },
  compact: {
    logo: { xs: 80, sm: 112 },
    name: { xs: '1.35rem', sm: '1.9rem' },
    tagline: { xs: '0.55rem', sm: '0.65rem' },
    gap: { xs: 1, sm: 1.5 },
  },
} as const;

/** The product wordmark: theme-aware logo + the two-tone name + tagline, as one responsive
 *  lockup reused wherever the brand appears. Sized by `size`, always smaller on mobile via
 *  per-breakpoint tokens (frontend.md §0/§1 — mobile-first, no magic values). */
export function AppBrandLockup({ size = 'hero', showTagline = true, nameComponent = 'span' }: AppBrandLockupProps) {
  const { mode } = useColorMode();
  const logoSrc = mode === 'dark' ? whiteFullRed : blackFullRed;
  const [first, second] = BRAND.nameParts;
  const s = SIZES[size];

  return (
    <Stack alignItems="center" sx={{ gap: s.gap, textAlign: 'center' }}>
      <Box
        component="img"
        src={logoSrc}
        alt={BRAND.name}
        sx={{ width: s.logo, height: 'auto', display: 'block' }}
      />

      <Typography
        component={nameComponent}
        sx={{
          fontFamily: fonts.display,
          fontWeight: 800,
          fontSize: s.name,
          lineHeight: 1,
          letterSpacing: '-0.03em',
          textTransform: 'uppercase',
          userSelect: 'none',
          m: 0,
        }}
      >
        {first}
        <Box component="span" sx={{ color: palette.alarm }}>
          {second}
        </Box>
      </Typography>

      {showTagline ? (
        <Typography
          sx={{
            fontFamily: fonts.mono,
            fontWeight: 600,
            fontSize: s.tagline,
            letterSpacing: '0.16em',
            textTransform: 'uppercase',
            color: 'text.secondary',
          }}
        >
          {BRAND.tagline}
        </Typography>
      ) : null}
    </Stack>
  );
}
