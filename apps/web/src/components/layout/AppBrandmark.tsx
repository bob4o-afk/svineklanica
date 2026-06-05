import { Box, Stack, Typography } from '@mui/material';
import { BRAND } from '@/config/brand';
import { fonts } from '@/theme/typography';

export interface AppBrandmarkProps {
  showTagline?: boolean;
  size?: 'sm' | 'md' | 'lg';
}

const FONT_SIZES: Record<NonNullable<AppBrandmarkProps['size']>, string> = {
  sm: '1rem',
  md: '1.25rem',
  lg: '1.75rem',
};

/** The wordmark: an accent block (acid in dark, ink in light) + the mono BRAND name. The display identity lives in
 *  config/brand.ts; this is the only place that paints it. */
export function AppBrandmark({ showTagline = false, size = 'md' }: AppBrandmarkProps) {
  return (
    <Stack direction="row" spacing={1} alignItems="center">
      <Box aria-hidden sx={{ width: 14, height: 14, bgcolor: 'primary.main', flexShrink: 0 }} />
      <Box>
        <Typography
          component="span"
          sx={{
            display: 'block',
            fontFamily: fonts.mono,
            fontWeight: 700,
            fontSize: FONT_SIZES[size],
            lineHeight: 1,
            letterSpacing: '-0.02em',
            textTransform: 'uppercase',
          }}
        >
          {BRAND.name}
        </Typography>
        {showTagline ? (
          <Typography component="span" variant="caption" color="text.secondary" sx={{ display: 'block', mt: 0.5 }}>
            {BRAND.tagline}
          </Typography>
        ) : null}
      </Box>
    </Stack>
  );
}
