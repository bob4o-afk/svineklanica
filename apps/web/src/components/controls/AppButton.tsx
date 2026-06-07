import { Button, type ButtonProps, CircularProgress } from '@mui/material';
import { forwardRef } from 'react';
import { Link as RouterLink } from 'react-router-dom';

export interface AppButtonProps extends ButtonProps {
  /** When set, the button renders as an in-app router link to this path. */
  to?: string;
  /** Show a spinner and disable the button while an action is in flight (frontend.md §7). */
  loading?: boolean;
}

/** The one button wrapper. Defaults to the filled punk variant; pass `to` to navigate. When
 *  `loading`, a spinner replaces the start icon and the button is disabled so it can't double-fire. */
export const AppButton = forwardRef<HTMLButtonElement, AppButtonProps>(function AppButton(
  { to, variant = 'contained', loading = false, disabled, startIcon, children, ...rest },
  ref,
) {
  const buttonProps: ButtonProps = {
    variant,
    disabled: disabled === true || loading,
    startIcon: loading ? <CircularProgress size={16} color="inherit" thickness={5} /> : startIcon,
    ...rest,
  };

  if (to !== undefined) {
    return (
      <Button ref={ref} component={RouterLink} to={to} {...buttonProps}>
        {children}
      </Button>
    );
  }
  return (
    <Button ref={ref} {...buttonProps}>
      {children}
    </Button>
  );
});
