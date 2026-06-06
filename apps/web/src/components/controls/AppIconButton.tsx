import { IconButton, type IconButtonProps, Tooltip } from '@mui/material';
import { forwardRef, type ReactNode } from 'react';

export interface AppIconButtonProps extends Omit<IconButtonProps, 'aria-label'> {
  /** Accessible label — used for both the tooltip and `aria-label` (never icon-only). */
  label: string;
  children: ReactNode;
}

/** Icon button with a mandatory accessible label + tooltip (a11y: no unlabeled controls). */
export const AppIconButton = forwardRef<HTMLButtonElement, AppIconButtonProps>(
  function AppIconButton({ label, children, ...rest }, ref) {
    return (
      <Tooltip title={label}>
        <IconButton ref={ref} aria-label={label} {...rest}>
          {children}
        </IconButton>
      </Tooltip>
    );
  },
);
