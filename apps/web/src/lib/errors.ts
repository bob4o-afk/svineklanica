/** A normalized application error. HTTP errors get a `status`; everything maps here. */
export class AppError extends Error {
  readonly status?: number;
  readonly code: string;

  constructor(message: string, options?: { status?: number; code?: string; cause?: unknown }) {
    super(message, options?.cause !== undefined ? { cause: options.cause } : undefined);
    this.name = 'AppError';
    this.code = options?.code ?? 'unknown';
    if (options?.status !== undefined) this.status = options.status;
  }
}

export function isAppError(value: unknown): value is AppError {
  return value instanceof AppError;
}

export function toAppError(value: unknown): AppError {
  if (isAppError(value)) return value;
  if (value instanceof Error) return new AppError(value.message, { cause: value });
  return new AppError('Unknown error', { cause: value });
}

/** Maps any error to an i18n key in the `errors` namespace for user display
 *  (ns-qualified with `:` so i18next resolves it regardless of the active default ns). */
export function toUserMessageKey(value: unknown): string {
  const err = toAppError(value);
  if (err.status === 404) return 'errors:not_found';
  if (err.status === 401 || err.status === 403) return 'errors:unauthorized';
  if (err.status !== undefined && err.status >= 500) return 'errors:server';
  if (err.status === undefined) return 'errors:network';
  return 'errors:generic';
}
