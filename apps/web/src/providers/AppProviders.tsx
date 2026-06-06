import { QueryClientProvider } from '@tanstack/react-query';
import { type ReactNode, Suspense, useMemo } from 'react';
import { HelmetProvider } from 'react-helmet-async';
import { I18nextProvider } from 'react-i18next';
import { AppLoadingScreen } from '@/components/feedback/AppLoadingScreen';
import { makeQueryClient } from '@/config/queryClient';
import i18n from '@/i18n';
import { AnalyticsProvider } from './AnalyticsProvider';
import { AuthProvider } from './AuthProvider';
import { ColorModeProvider } from './ColorModeProvider';
import { ToastProvider } from './ToastProvider';

/** Composes every cross-cutting provider. HelmetProvider sits outermost so any route can manage
 *  the document head; ColorModeProvider owns the MUI theme + CssBaseline, so it wraps everything
 *  that renders MUI (toasts, etc.). */
export function AppProviders({ children }: { children: ReactNode }) {
  const queryClient = useMemo(() => makeQueryClient(), []);
  return (
    <HelmetProvider>
      <I18nextProvider i18n={i18n}>
        <ColorModeProvider>
          <QueryClientProvider client={queryClient}>
            <AuthProvider>
              <ToastProvider>
                <AnalyticsProvider>
                  <Suspense fallback={<AppLoadingScreen />}>{children}</Suspense>
                </AnalyticsProvider>
              </ToastProvider>
            </AuthProvider>
          </QueryClientProvider>
        </ColorModeProvider>
      </I18nextProvider>
    </HelmetProvider>
  );
}
