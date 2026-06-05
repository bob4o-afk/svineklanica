import { render, waitFor } from '@testing-library/react';
import { HelmetProvider } from 'react-helmet-async';
import { describe, expect, it } from 'vitest';
import { AppSeo } from './AppSeo';

describe('AppSeo', () => {
  it('sets a brand-suffixed document title', async () => {
    render(
      <HelmetProvider>
        <AppSeo title="Сигнали" />
      </HelmetProvider>,
    );
    await waitFor(() => expect(document.title).toContain('Сигнали'));
    expect(document.title).toContain('CORRUPTION FUCKER');
  });

  it('emits a noindex robots meta when requested', async () => {
    render(
      <HelmetProvider>
        <AppSeo title="Админ" noindex />
      </HelmetProvider>,
    );
    await waitFor(() =>
      expect(document.querySelector('meta[name="robots"]')?.getAttribute('content')).toContain(
        'noindex',
      ),
    );
  });
});
