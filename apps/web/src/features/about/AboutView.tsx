import { List, ListItem, ListItemText, Stack, Typography } from '@mui/material';
import { useTranslation } from 'react-i18next';

const PRINCIPLE_KEYS = ['sourced', 'public', 'patterns', 'oss'] as const;
const SECURITY_KEYS = ['csp', 'noTracking', 'sanitized'] as const;

/** Methodology + security posture — the pitch's "why trust this" page, and demonstrable proof
 *  of the sourcing/security discipline (plan §Security · CLAUDE.md §0). */
export function AboutView() {
  const { t } = useTranslation();

  return (
    <Stack spacing={4}>
      <Stack spacing={1}>
        <Typography variant="h4" component="h1">
          {t('about:title')}
        </Typography>
        <Typography variant="body1" color="text.secondary">
          {t('about:intro')}
        </Typography>
      </Stack>

      <Stack spacing={1}>
        <Typography variant="h5" component="h2">
          {t('about:principles.title')}
        </Typography>
        <List dense sx={{ listStyleType: 'disc', pl: 3 }}>
          {PRINCIPLE_KEYS.map((key) => (
            <ListItem key={key} sx={{ display: 'list-item', py: 0.25 }} disableGutters>
              <ListItemText primary={t(`about:principles.${key}`)} />
            </ListItem>
          ))}
        </List>
      </Stack>

      <Stack spacing={1}>
        <Typography variant="h5" component="h2">
          {t('about:security.title')}
        </Typography>
        <List dense sx={{ listStyleType: 'disc', pl: 3 }}>
          {SECURITY_KEYS.map((key) => (
            <ListItem key={key} sx={{ display: 'list-item', py: 0.25 }} disableGutters>
              <ListItemText primary={t(`about:security.${key}`)} />
            </ListItem>
          ))}
        </List>
      </Stack>
    </Stack>
  );
}
