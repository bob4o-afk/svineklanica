import { Stack, Typography } from '@mui/material';
import type { Icon } from '@phosphor-icons/react';
import type { ElementType, ReactNode } from 'react';

export interface AppEmptyStateProps {
  title: string;
  description?: string;
  icon?: Icon;
  action?: ReactNode;
  /** Semantic element for the title. Default `p`; pass `h1` when this is a standalone page. */
  titleComponent?: ElementType;
}

/** Neutral "nothing here" state — never leave the user staring at a blank area. */
export function AppEmptyState({
  title,
  description,
  icon: IconComponent,
  action,
  titleComponent = 'p',
}: AppEmptyStateProps) {
  return (
    <Stack spacing={1.5} alignItems="center" textAlign="center" sx={{ py: 6, px: 2 }}>
      {IconComponent ? <IconComponent size={40} weight="duotone" /> : null}
      <Typography variant="h6" component={titleComponent}>
        {title}
      </Typography>
      {description !== undefined ? (
        <Typography variant="body2" color="text.secondary" sx={{ maxWidth: 420 }}>
          {description}
        </Typography>
      ) : null}
      {action}
    </Stack>
  );
}
