import { Box, Paper, Stack, Typography } from '@mui/material';
import { ChartLineIcon } from '@phosphor-icons/react';
import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { AppEmptyState } from '@/components/feedback/AppEmptyState';
import { AppErrorState } from '@/components/feedback/AppErrorState';
import { AppSkeleton } from '@/components/feedback/AppSkeleton';
import { AppSourceLink } from '@/components/flags/AppSourceLink';
import { fonts } from '@/theme/typography';
import type { SourceRef } from '@/types/api';

export interface AppChartFrameProps {
  /** Heading shown above the chart. */
  title: string;
  /** Optional one-line context under the title. */
  subtitle?: string;
  isPending: boolean;
  isError: boolean;
  error?: unknown;
  onRetry?: () => void;
  /** True when the query succeeded but there is nothing to plot. */
  isEmpty?: boolean;
  emptyTitle?: string;
  emptyMessage?: string;
  /** Primary sources behind the data — every chart is a sourced claim (CLAUDE.md §0). */
  sources?: SourceRef[];
  children: ReactNode;
}

/** Shared frame for every visualization: title, the loading / error / empty states, the
 *  chart itself, and a primary-source attribution footer. One implementation reused by the
 *  price chart, the serial-winner graph, and the region map — no per-chart boilerplate. */
export function AppChartFrame({
  title,
  subtitle,
  isPending,
  isError,
  error,
  onRetry,
  isEmpty = false,
  emptyTitle,
  emptyMessage,
  sources,
  children,
}: AppChartFrameProps) {
  const { t } = useTranslation();

  let body: ReactNode;
  if (isPending) {
    body = <AppSkeleton count={1} height={320} />;
  } else if (isError) {
    body = (
      <AppErrorState
        title={t('viz:error.title')}
        message={t('viz:error.body')}
        {...(error !== undefined ? { error } : {})}
        {...(onRetry ? { onRetry } : {})}
      />
    );
  } else if (isEmpty) {
    body = (
      <AppEmptyState
        icon={ChartLineIcon}
        title={emptyTitle ?? t('viz:empty.title')}
        description={emptyMessage ?? t('viz:empty.body')}
      />
    );
  } else {
    body = children;
  }

  const showSources = !isPending && !isError && !isEmpty;

  return (
    <Paper variant="outlined" sx={{ p: { xs: 2, sm: 3 } }}>
      <Stack spacing={2}>
        <Box>
          <Typography
            component="h2"
            sx={{
              fontFamily: fonts.display,
              fontWeight: 800,
              fontSize: { xs: '1.25rem', sm: '1.5rem' },
              lineHeight: 1.1,
            }}
          >
            {title}
          </Typography>
          {subtitle !== undefined ? (
            <Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>
              {subtitle}
            </Typography>
          ) : null}
        </Box>

        {body}

        {showSources && sources && sources.length > 0 ? (
          <Box>
            <Typography
              component="p"
              sx={{
                fontFamily: fonts.mono,
                fontWeight: 700,
                fontSize: '0.65rem',
                letterSpacing: '0.12em',
                textTransform: 'uppercase',
                color: 'text.secondary',
                mb: 0.5,
              }}
            >
              {t('viz:sources')}
            </Typography>
            <Stack spacing={0.5}>
              {sources.map((source, index) => (
                <AppSourceLink key={`${source.url}-${index}`} source={source} />
              ))}
            </Stack>
          </Box>
        ) : null}
      </Stack>
    </Paper>
  );
}
