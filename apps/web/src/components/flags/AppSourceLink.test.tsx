import { screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import type { SourceRef } from '@/types/api';
import { renderWithProviders } from '@/test/renderWithProviders';
import { AppSourceLink } from './AppSourceLink';

function source(url: string): SourceRef {
  return { url, label: 'Профил на купувача', fetched_at: '2026-06-01T12:00:00Z' };
}

describe('AppSourceLink (security)', () => {
  it('renders a safe external link for https URLs', () => {
    renderWithProviders(<AppSourceLink source={source('https://registar.example/doc/1')} />);
    const link = screen.getByRole('link', { name: /Профил на купувача/ });
    expect(link).toHaveAttribute('href', 'https://registar.example/doc/1');
    expect(link).toHaveAttribute('target', '_blank');
    expect(link).toHaveAttribute('rel', 'noopener noreferrer');
    // Anti-phishing: the hostname is shown so a reader sees where the click goes.
    expect(screen.getByText('(registar.example)')).toBeInTheDocument();
  });

  it('rejects a javascript: URL — renders a warning, never a link', () => {
    renderWithProviders(<AppSourceLink source={source('javascript:alert(document.cookie)')} />);
    expect(screen.queryByRole('link')).toBeNull();
    expect(screen.getByText('Липсва източник')).toBeInTheDocument();
  });

  it('rejects a data: URL', () => {
    renderWithProviders(<AppSourceLink source={source('data:text/html,<script>1</script>')} />);
    expect(screen.queryByRole('link')).toBeNull();
    expect(screen.getByText('Липсва източник')).toBeInTheDocument();
  });

  it('rejects a relative / schemeless URL', () => {
    renderWithProviders(<AppSourceLink source={source('/admin')} />);
    expect(screen.queryByRole('link')).toBeNull();
  });
});
