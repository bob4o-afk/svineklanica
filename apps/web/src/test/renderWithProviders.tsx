import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, type RenderResult } from '@testing-library/react';
import type { ReactElement, ReactNode } from 'react';
import { HelmetProvider } from 'react-helmet-async';
import { I18nextProvider } from 'react-i18next';
import { MemoryRouter } from 'react-router-dom';
import i18n from '@/i18n';
import { AuthProvider } from '@/providers/AuthProvider';
import { ColorModeProvider } from '@/providers/ColorModeProvider';
import { ToastProvider } from '@/providers/ToastProvider';

export interface RenderWithProvidersOptions {
  /** Initial router entries (defaults to '/'). */
  routerEntries?: string[];
}

/** Renders a component inside the full app provider stack with a fresh, retry-free QueryClient
 *  and an in-memory router. Mirrors AppProviders so tests exercise real context wiring. */
export function renderWithProviders(
  ui: ReactElement,
  options: RenderWithProvidersOptions = {},
): RenderResult & { queryClient: QueryClient } {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false, gcTime: 0 } },
  });
  const entries = options.routerEntries ?? ['/'];

  function Wrapper({ children }: { children: ReactNode }) {
    return (
      <HelmetProvider>
        <I18nextProvider i18n={i18n}>
          <ColorModeProvider>
            <QueryClientProvider client={queryClient}>
              <AuthProvider>
                <ToastProvider>
                  <MemoryRouter
                  initialEntries={entries}
                  future={{ v7_startTransition: true, v7_relativeSplatPath: true }}
                >
                  {children}
                </MemoryRouter>
                </ToastProvider>
              </AuthProvider>
            </QueryClientProvider>
          </ColorModeProvider>
        </I18nextProvider>
      </HelmetProvider>
    );
  }

  return Object.assign(render(ui, { wrapper: Wrapper }), { queryClient });
}
