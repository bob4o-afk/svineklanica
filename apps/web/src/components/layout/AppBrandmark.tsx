import { Box, Stack, Typography } from '@mui/material';
import { BRAND } from '@/config/brand';
import { useColorMode } from '@/hooks/useColorMode';
import { fonts } from '@/theme/typography';
import { palette } from '@/theme/tokens';

// Eye-only logos — compact enough for the header.
import blackEyeRed from '@/assets/logos/black_eye_red.svg';
import whiteEyeRed from '@/assets/logos/white_eye_red.svg';

export interface AppBrandmarkProps {
  /** Height of the eye icon in px. */
  height?: number;
}

/** Header wordmark: eye logo + split brand name (СВИНЕ white / КЛАНИЦА red). */
export function AppBrandmark({ height = 32 }: AppBrandmarkProps) {
  const { mode } = useColorMode();
  const isDark = mode === 'dark';
  const src = isDark ? whiteEyeRed : blackEyeRed;

  const [first, second] = BRAND.nameParts;

  return (
    <Stack direction="row" spacing={1} alignItems="center">
      <Box
        component="img"
        src={src}
        alt=""
        aria-hidden
        sx={{ height, width: 'auto', display: 'block', userSelect: 'none', flexShrink: 0 }}
      />
      <Typography
        component="span"
        sx={{
          fontFamily: fonts.display,
          fontWeight: 800,
          fontSize: height * 0.5,
          lineHeight: 1,
          letterSpacing: '-0.02em',
          textTransform: 'uppercase',
          userSelect: 'none',
          whiteSpace: 'nowrap',
        }}
      >
        {first}
        <Box component="span" sx={{ color: palette.alarm }}>
          {second}
        </Box>
      </Typography>
    </Stack>
  );
}
