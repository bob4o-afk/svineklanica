import axios, { AxiosError, type AxiosInstance } from 'axios';
import { env } from '@/config/env';
import { AppError } from '@/lib/errors';
import { logger } from '@/lib/logger';

/** The ONLY axios instance in the app. No raw fetch/axios anywhere else.
 *  Sanctum SPA cookie auth: send the session cookie + XSRF header, no token in JS. */
export const http: AxiosInstance = axios.create({
  baseURL: env.apiUrl,
  withCredentials: true,
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
  headers: { Accept: 'application/json' },
});

http.interceptors.response.use(
  (response) => response,
  (error: unknown) => {
    if (error instanceof AxiosError) {
      const status = error.response?.status;
      logger.error('http_error', { url: error.config?.url, status });
      return Promise.reject(
        new AppError(error.message, {
          code: 'http_error',
          cause: error,
          ...(status !== undefined ? { status } : {}),
        }),
      );
    }
    logger.error('http_unknown_error', {});
    return Promise.reject(new AppError('Network error', { code: 'network', cause: error }));
  },
);
