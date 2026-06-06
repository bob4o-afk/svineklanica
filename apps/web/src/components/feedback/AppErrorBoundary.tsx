import { Component, type ErrorInfo, type ReactNode } from 'react';
import { toAppError } from '@/lib/errors';
import { logger } from '@/lib/logger';
import { AppErrorState } from './AppErrorState';

export interface AppErrorBoundaryProps {
  children: ReactNode;
  /** Optional custom fallback; defaults to <AppErrorState/>. */
  fallback?: ReactNode;
}

interface AppErrorBoundaryState {
  hasError: boolean;
}

/** Catches render-time exceptions in the subtree, routes them to the logger, and shows a
 *  recoverable error surface instead of a white screen. (frontend.md §4 logging, §7 feedback) */
export class AppErrorBoundary extends Component<AppErrorBoundaryProps, AppErrorBoundaryState> {
  constructor(props: AppErrorBoundaryProps) {
    super(props);
    this.state = { hasError: false };
    this.reset = this.reset.bind(this);
  }

  static getDerivedStateFromError(): AppErrorBoundaryState {
    return { hasError: true };
  }

  componentDidCatch(error: unknown, info: ErrorInfo): void {
    const appError = toAppError(error);
    logger.error('react_error_boundary', {
      message: appError.message,
      code: appError.code,
      componentStack: info.componentStack ?? '',
    });
  }

  reset(): void {
    this.setState({ hasError: false });
  }

  render(): ReactNode {
    if (this.state.hasError) {
      return this.props.fallback ?? <AppErrorState onRetry={this.reset} />;
    }
    return this.props.children;
  }
}
