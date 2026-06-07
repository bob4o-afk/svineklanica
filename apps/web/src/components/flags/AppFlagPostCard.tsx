import { Box, Card, CardActionArea, CardActions, CardContent, Stack, Typography } from '@mui/material';
import { EyeIcon } from '@phosphor-icons/react';
import { useTranslation } from 'react-i18next';
import { Link as RouterLink } from 'react-router-dom';
import { formatDate } from '@/lib/date';
import { formatNumber } from '@/lib/format';
import { flagTypeMeta } from '@/lib/flags';
import { paths } from '@/routes/paths';
import { fonts } from '@/theme/typography';
import { palette } from '@/theme/tokens';
import type { FlagPost, FlagSubject } from '@/types/api';
import { AppCategoryBadge } from './AppCategoryBadge';
import { AppEvidenceList } from './AppEvidenceList';
import { AppFlagBadge } from './AppFlagBadge';
import { AppSectorBadge } from './AppSectorBadge';
import { AppSeverityChip } from './AppSeverityChip';
import { AppSourceLink } from './AppSourceLink';
import { AppSphereBadge } from './AppSphereBadge';
import { AppTag } from './AppTag';

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
  const typeMeta = flagTypeMeta[flag.type];
  const headline = flag.title ?? (typeMeta !== undefined ? t(typeMeta.i18nKey) : flag.type);
  const subject = subjectLine(flag.subject);
  const primarySource = flag.sources[0];

  return (
    <Card className="punk-card">
      {/* 2px alarm-red top border marks every card as a potential scandal */}
      <Box sx={{ height: 2, bgcolor: palette.alarm, flexShrink: 0 }} />
      <CardActionArea component={RouterLink} to={paths.post(flag.public_id)}>
        <CardContent>
          {/* Sphere → Category → severity (+score %) — the §1.0 hierarchy, then type/sector/tags. */}
          <Stack direction="row" spacing={1} flexWrap="wrap" useFlexGap sx={{ mb: 1.5 }} alignItems="center">
            {flag.sphere !== undefined ? <AppSphereBadge sphere={flag.sphere} /> : null}
            {flag.corruption_category !== undefined ? (
              <AppCategoryBadge category={flag.corruption_category} />
            ) : null}
            <AppSeverityChip severity={flag.severity} {...(flag.score !== undefined ? { score: flag.score } : {})} />
            <AppFlagBadge type={flag.type} />
            {flag.category !== undefined ? <AppSectorBadge sector={flag.category} /> : null}
            {(flag.tags ?? []).map((tag) => (
              <AppTag key={tag} tag={tag} />
            ))}
          </Stack>
          <Typography
            variant="h6"
            component="h3"
            gutterBottom
            sx={{ fontFamily: fonts.display, fontWeight: 800 }}
          >
            {headline}
          </Typography>
          {subject !== '' ? (
            <Typography variant="subtitle2" color="text.secondary" gutterBottom sx={{ fontFamily: fonts.mono, fontSize: '0.75rem', letterSpacing: '0.04em' }}>
              {subject}
            </Typography>
          ) : null}
          <Typography variant="body2" sx={{ mb: 2, lineHeight: 1.65 }}>
            {flag.explanation_bg}
          </Typography>
          <AppEvidenceList items={flag.evidence} max={2} />
        </CardContent>
      </CardActionArea>
      <CardActions sx={{ justifyContent: 'space-between', flexWrap: 'wrap', gap: 1, px: 2, py: 1, borderTop: `1px solid ${palette.alarm}22` }}>
        {primarySource !== undefined ? (
          <AppSourceLink source={primarySource} />
        ) : (
          <Typography variant="caption" color="warning.main">
            {t('flags:card.noSource')}
          </Typography>
        )}
        <Stack direction="row" spacing={1.5} alignItems="center" sx={{ color: 'text.secondary' }}>
          <Stack
            direction="row"
            spacing={0.5}
            alignItems="center"
            aria-label={t('flags:card.views')}
            title={t('flags:card.views')}
          >
            <EyeIcon size={14} />
            <Typography variant="caption" sx={{ fontFamily: fonts.mono, letterSpacing: '0.06em' }}>
              {formatNumber(flag.view_count ?? 0)}
            </Typography>
          </Stack>
          <Typography variant="caption" sx={{ fontFamily: fonts.mono, letterSpacing: '0.06em' }}>
            {t('flags:card.detectedAt')}: {formatDate(flag.detected_at)}
          </Typography>
        </Stack>
      </CardActions>
    </Card>
  );
}
