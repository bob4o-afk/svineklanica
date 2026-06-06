import { Button, type ButtonProps } from '@mui/material';
import { forwardRef } from 'react';
import { Link as RouterLink } from 'react-router-dom';

export interface AppButtonProps extends ButtonProps {
  /** When set, the button renders as an in-app router link to this path. */
  to?: string;
}

/** The one button wrapper. Defaults to the filled punk variant; pass `to` to navigate. */
export const AppButton = forwardRef<HTMLButtonElement, AppButtonProps>(function AppButton(
  { to, variant = 'contained', ...rest },
  ref,
) {
  if (to !== undefined) {
    return <Button ref={ref} component={RouterLink} to={to} variant={variant} {...rest} />;
  }
  return <Button ref={ref} variant={variant} {...rest} />;
});
