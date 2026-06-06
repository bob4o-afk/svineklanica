import { Box, Typography } from '@mui/material';
import { useTranslation } from 'react-i18next';
import { env } from '@/config/env';

/** Loud, honest "this is invented demo data" bar — shown whenever mocks are on, so fake
 *  flags are never mistaken for real cases (anti-disinformation; CLAUDE.md §0). */
export function AppDemoBanner() {
  const { t } = useTranslation();
  if (!env.enableMocks) return null;

  return (
    <Box
      role="note"
      sx={{
        position: 'relative',
        zIndex: 1,
        // alarm-red bar with near-black text -> ~5.7:1, clears WCAG AA. The "fake data" honesty
        // bar must be readable (CLAUDE.md §0).
        bgcolor: 'error.main',
        color: 'common.black',
        textAlign: 'center',
        py: 0.5,
        px: 2,
      }}
    >
      <Typography variant="overline" sx={{ textTransform: 'uppercase', lineHeight: 1.6, fontWeight: 700 }}>
        {t('common:demo.banner')}
      </Typography>
    </Box>
  );
}
