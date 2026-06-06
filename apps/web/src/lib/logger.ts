import { env } from '@/config/env';
import { captureError } from '@/lib/monitoring';

type LogContext = Record<string, unknown>;
type LogLevel = 'error' | 'warn' | 'info';

/** The ONE sanctioned place that may touch `console` (ESLint no-console is disabled
 *  for that line only). `error` also forwards to remote monitoring (Sentry) via `captureError`. */
function emit(level: LogLevel, message: string, context?: LogContext): void {
  // eslint-disable-next-line no-console
  const sink = level === 'error' ? console.error : level === 'warn' ? console.warn : console.info;
  if (context === undefined) {
    sink(`[${level}] ${message}`);
  } else {
    sink(`[${level}] ${message}`, context);
  }
}

export const logger = {
  error(message: string, context?: LogContext): void {
    emit('error', message, context);
    captureError(message, context);
  },
  warn(message: string, context?: LogContext): void {
    emit('warn', message, context);
  },
  info(message: string, context?: LogContext): void {
    if (!env.isDev) return;
    emit('info', message, context);
  },
  debug(message: string, context?: LogContext): void {
    if (!env.isDev) return;
    emit('info', `debug: ${message}`, context);
  },
};
