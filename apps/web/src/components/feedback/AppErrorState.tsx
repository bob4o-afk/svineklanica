import { Box, Stack, Typography } from '@mui/material';
import { WarningIcon } from '@phosphor-icons/react';
import { useTranslation } from 'react-i18next';
import { AppButton } from '@/components/controls/AppButton';
import { toUserMessageKey } from '@/lib/errors';

export interface AppErrorStateProps {
  /** Optional heading; falls back to just the message. */
  title?: string;
  /** Explicit message; otherwise derived from `error` via the i18n error map. */
  message?: string;
  error?: unknown;
  onRetry?: () => void;
}

/** Standard error surface with a retry affordance (frontend.md §7). */
export function AppErrorState({ title, message, error, onRetry }: AppErrorStateProps) {
  const { t } = useTranslation();
  const resolved = message ?? t(error !== undefined ? toUserMessageKey(error) : 'errors:generic');

  return (
    <Stack spacing={1.5} alignItems="center" textAlign="center" sx={{ py: 6, px: 2 }}>
      <Box sx={{ color: 'error.main', lineHeight: 0 }}>
        <WarningIcon size={40} weight="fill" />
      </Box>
      {title !== undefined ? (
        <Typography variant="h6" component="p">
          {title}
        </Typography>
      ) : null}
      <Typography variant="body2" color="text.secondary" sx={{ maxWidth: 420 }}>
        {resolved}
      </Typography>
      {onRetry !== undefined ? (
        <AppButton variant="outlined" onClick={onRetry}>
          {t('common:actions.retry')}
        </AppButton>
      ) : null}
    </Stack>
  );
}
