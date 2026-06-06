import { Box, Link as MuiLink, Stack, Typography } from '@mui/material';
import { GithubLogoIcon } from '@phosphor-icons/react';
import { useTranslation } from 'react-i18next';
import { Link as RouterLink } from 'react-router-dom';
import { BRAND } from '@/config/brand';
import { paths } from '@/routes/paths';

/** Footer: the sourcing promise + the OSS repo link (external, opened safely). */
export function AppFooter() {
  const { t } = useTranslation();

  return (
    <Box component="footer" sx={{ borderTop: 1, borderColor: 'divider', mt: 6, py: 3, bgcolor: 'background.default', position: 'relative', zIndex: 1 }}>
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
        {/* Discreet editor entry — back-office, never advertised in the main nav. */}
        <MuiLink
          component={RouterLink}
          to={paths.adminLogin}
          variant="caption"
          color="text.secondary"
          sx={{ opacity: 0.6 }}
        >
          {t('common:nav.admin')}
        </MuiLink>
      </Stack>
    </Box>
  );
}
