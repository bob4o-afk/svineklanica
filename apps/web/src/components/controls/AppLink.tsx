import { Link as MuiLink, type LinkProps as MuiLinkProps } from '@mui/material';
import { forwardRef } from 'react';
import { Link as RouterLink } from 'react-router-dom';

export interface AppLinkProps extends Omit<MuiLinkProps, 'href' | 'component'> {
  to: string;
}

/** In-app text link. For EXTERNAL/untrusted URLs use AppSourceLink (scheme-validated). */
export const AppLink = forwardRef<HTMLAnchorElement, AppLinkProps>(function AppLink(
  { to, ...rest },
  ref,
) {
  return <MuiLink ref={ref} component={RouterLink} to={to} {...rest} />;
});
