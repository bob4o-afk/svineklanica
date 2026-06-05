import { Card, CardActionArea, CardActions, CardContent, Stack, Typography } from '@mui/material';
import { useTranslation } from 'react-i18next';
import { Link as RouterLink } from 'react-router-dom';
import { formatDate } from '@/lib/date';
import { flagTypeMeta } from '@/lib/flags';
import { paths } from '@/routes/paths';
import type { FlagPost, FlagSubject } from '@/types/api';
import { AppEvidenceList } from './AppEvidenceList';
import { AppFlagBadge } from './AppFlagBadge';
import { AppSeverityChip } from './AppSeverityChip';
import { AppSourceLink } from './AppSourceLink';

/** The single subject line for a flag, chosen by its subject type. */
function subjectLine(subject: FlagSubject): string {
  if (subject.type === 'company' && subject.company !== undefined) return subject.company.name;
  if (subject.type === 'authority' && subject.authority !== undefined) return subject.authority.name;
  if (subject.tender !== undefined) return subject.tender.title;
  return subject.authority?.name ?? subject.company?.name ?? '';
}

export interface AppFlagPostCardProps {
  flag: FlagPost;
}

/** Feed card: punk headline + severity/type badges + neutral sourced body. The source link
 *  sits OUTSIDE the CardActionArea so we never nest an <a> inside the navigation button. */
export function AppFlagPostCard({ flag }: AppFlagPostCardProps) {
  const { t } = useTranslation();
  const headline = flag.title ?? t(flagTypeMeta[flag.type].i18nKey);
  const subject = subjectLine(flag.subject);
  const primarySource = flag.sources[0];

  return (
    <Card>
      <CardActionArea component={RouterLink} to={paths.post(flag.public_id)}>
        <CardContent>
          <Stack direction="row" spacing={1} flexWrap="wrap" useFlexGap sx={{ mb: 1 }}>
            <AppSeverityChip severity={flag.severity} />
            <AppFlagBadge type={flag.type} />
          </Stack>
          <Typography variant="h6" component="h3" gutterBottom>
            {headline}
          </Typography>
          {subject !== '' ? (
            <Typography variant="subtitle2" color="text.secondary" gutterBottom>
              {subject}
            </Typography>
          ) : null}
          <Typography variant="body2" sx={{ mb: 2 }}>
            {flag.explanation_bg}
          </Typography>
          <AppEvidenceList items={flag.evidence} max={2} />
        </CardContent>
      </CardActionArea>
      <CardActions sx={{ justifyContent: 'space-between', flexWrap: 'wrap', gap: 1, px: 2, py: 1 }}>
        {primarySource !== undefined ? (
          <AppSourceLink source={primarySource} />
        ) : (
          <Typography variant="caption" color="warning.main">
            {t('flags:card.noSource')}
          </Typography>
        )}
        <Typography variant="caption" color="text.secondary">
          {t('flags:card.detectedAt')}: {formatDate(flag.detected_at)}
        </Typography>
      </CardActions>
    </Card>
  );
}
