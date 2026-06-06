import { Box, Stack, Typography } from '@mui/material';
import { LightningIcon } from '@phosphor-icons/react';
import { useTranslation } from 'react-i18next';
import { radii } from '@/theme/tokens';

export interface AppTldrProps {
  text: string;
}

/** „Накратко" — a one-line plain-language gist shown at the top of a post, so a reader gets the
 *  point before deciding to read the full explanation. The accent left-border (primary.main:
 *  acid in dark, ink in light) marks it without relying on lime, which is unused in light mode. */
export function AppTldr({ text }: AppTldrProps) {
  const { t } = useTranslation();

  return (
    <Box
      sx={{
        borderLeft: 4,
        borderColor: 'primary.main',
        bgcolor: 'action.hover',
        borderTopRightRadius: radii.sm,
        borderBottomRightRadius: radii.sm,
        px: 2,
        py: 1.5,
      }}
    >
      <Stack direction="row" spacing={0.75} alignItems="center" sx={{ mb: 0.5 }}>
        <LightningIcon size={14} weight="fill" aria-hidden />
        <Typography variant="overline" color="text.secondary">
          {t('post:tldr')}
        </Typography>
      </Stack>
      <Typography variant="body1" sx={{ fontWeight: 500 }}>
        {text}
      </Typography>
    </Box>
  );
}
