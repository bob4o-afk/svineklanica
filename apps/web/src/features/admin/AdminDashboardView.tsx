import { Box, Card, CardContent, Chip, Stack, Typography } from '@mui/material';
import { ClipboardTextIcon, DatabaseIcon, type Icon } from '@phosphor-icons/react';
import { useTranslation } from 'react-i18next';
import { AppButton } from '@/components/controls/AppButton';
import { usePendingFlags } from '@/hooks/queries/usePendingFlags';
import { useSources } from '@/hooks/queries/useSources';
import { paths } from '@/routes/paths';

interface DashboardCardProps {
  icon: Icon;
  title: string;
  body: string;
  to: string;
  count?: number;
}

function DashboardCard({ icon: IconComponent, title, body, to, count }: DashboardCardProps) {
  const { t } = useTranslation();
  return (
    <Card variant="outlined">
      <CardContent>
        <Stack spacing={1.5}>
          <Stack direction="row" alignItems="center" justifyContent="space-between">
            <Stack direction="row" alignItems="center" spacing={1}>
              <IconComponent size={22} weight="duotone" />
              <Typography variant="h6" component="h2">
                {title}
              </Typography>
            </Stack>
            {count !== undefined ? <Chip size="small" color="primary" label={count} /> : null}
          </Stack>
          <Typography variant="body2" color="text.secondary">
            {body}
          </Typography>
          <AppButton variant="outlined" size="small" to={to} sx={{ alignSelf: 'flex-start' }}>
            {t('admin:dashboard.open')}
          </AppButton>
        </Stack>
      </CardContent>
    </Card>
  );
}

/** Editor landing: jump-off cards to the review queue and the sources registry, each with a live
 *  count so an editor sees at a glance what's waiting. */
export function AdminDashboardView() {
  const { t } = useTranslation();
  const pending = usePendingFlags();
  const sources = useSources();

  return (
    <Stack spacing={3}>
      <Typography variant="h4" component="h1">
        {t('admin:dashboard.title')}
      </Typography>
      <Box sx={{ display: 'grid', gap: 2, gridTemplateColumns: { xs: '1fr', sm: '1fr 1fr' } }}>
        <DashboardCard
          icon={ClipboardTextIcon}
          title={t('admin:dashboard.pendingCard')}
          body={t('admin:dashboard.pendingCardBody')}
          to={paths.adminPending}
          {...(pending.data !== undefined ? { count: pending.data.total } : {})}
        />
        <DashboardCard
          icon={DatabaseIcon}
          title={t('admin:dashboard.sourcesCard')}
          body={t('admin:dashboard.sourcesCardBody')}
          to={paths.adminSources}
          {...(sources.data !== undefined ? { count: sources.data.length } : {})}
        />
      </Box>
    </Stack>
  );
}
