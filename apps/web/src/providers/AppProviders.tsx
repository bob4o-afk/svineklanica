import { QueryClientProvider } from '@tanstack/react-query';
import { PersistQueryClientProvider } from '@tanstack/react-query-persist-client';
import { type ReactNode, Suspense, useState } from 'react';
import { HelmetProvider } from 'react-helmet-async';
import { I18nextProvider } from 'react-i18next';
import { AppLoadingScreen } from '@/components/feedback/AppLoadingScreen';
import { makePersistOptions, makeQueryClient } from '@/config/queryClient';
import i18n from '@/i18n';
import { AnalyticsProvider } from './AnalyticsProvider';
import { AuthProvider } from './AuthProvider';
import { ColorModeProvider } from './ColorModeProvider';
import { ToastProvider } from './ToastProvider';

/** Composes every cross-cutting provider. HelmetProvider sits outermost so any route can manage
 *  the document head; ColorModeProvider owns the MUI theme + CssBaseline, so it wraps everything
 *  that renders MUI (toasts, etc.). */
export function AppProviders({ children }: { children: ReactNode }) {
  // useState (not useMemo) so the client is a stable singleton — React can discard a useMemo,
  // which would drop the whole query cache and leave the UI blank until a manual reload.
  const [queryClient] = useState(() => makeQueryClient());
  // Persist the cache to localStorage so a full reload rehydrates the feed instantly instead of
  // refetching from scratch (frontend.md §9). Null only if storage is unavailable.
  const [persistOptions] = useState(() => makePersistOptions());

  const tree = (
    <AuthProvider>
      <ToastProvider>
        <AnalyticsProvider>
          <Suspense fallback={<AppLoadingScreen />}>{children}</Suspense>
        </AnalyticsProvider>
      </ToastProvider>
    </AuthProvider>
  );

  return (
    <HelmetProvider>
      <I18nextProvider i18n={i18n}>
        <ColorModeProvider>
          {persistOptions !== null ? (
            <PersistQueryClientProvider client={queryClient} persistOptions={persistOptions}>
              {tree}
            </PersistQueryClientProvider>
          ) : (
            <QueryClientProvider client={queryClient}>{tree}</QueryClientProvider>
          )}
        </ColorModeProvider>
      </I18nextProvider>
    </HelmetProvider>
  );
}
