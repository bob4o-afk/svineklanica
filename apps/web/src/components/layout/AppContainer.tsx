import { Container, type ContainerProps } from '@mui/material';

export type AppContainerProps = ContainerProps;

/** App-wide content width. Defaults to `md` so reading columns stay comfortable on desktop
 *  while staying full-width on phones (mobile-first). */
export function AppContainer({ maxWidth = 'md', children, ...rest }: AppContainerProps) {
  return (
    <Container maxWidth={maxWidth} {...rest}>
      {children}
    </Container>
  );
}
