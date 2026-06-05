import { Box, Link as MuiLink, Stack, Typography } from '@mui/material';
import { GithubLogoIcon } from '@phosphor-icons/react';
import { useTranslation } from 'react-i18next';
import { BRAND } from '@/config/brand';

/** Footer: the sourcing promise + the OSS repo link (external, opened safely). */
export function AppFooter() {
  const { t } = useTranslation();

  return (
    <Box component="footer" sx={{ borderTop: 1, borderColor: 'divider', mt: 6, py: 3 }}>
      <Stack spacing={1} alignItems="center" textAlign="center">
        <Typography variant="caption" color="text.secondary">
          {t('common:footer.sources')}
        </Typography>
        <MuiLink
          href={BRAND.repoUrl}
          target="_blank"
          rel="noopener noreferrer"
          variant="caption"
          sx={{ display: 'inline-flex', alignItems: 'center', gap: 0.5 }}
        >
          <GithubLogoIcon size={14} />
          {t('common:footer.oss')}
        </MuiLink>
        <Typography variant="overline" color="text.secondary">
          {BRAND.name}
        </Typography>
      </Stack>
    </Box>
  );
}
