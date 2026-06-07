import { screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import type { FlagPost } from '@/types/api';
import { renderWithProviders } from '@/test/renderWithProviders';
import { AppFlagPostCard } from './AppFlagPostCard';

function makeFlag(overrides: Partial<FlagPost> = {}): FlagPost {
  return {
    public_id: 'flag-test-1',
    type: 'price_discrepancy',
    severity: 'high',
    status: 'approved',
    subject: {
      type: 'tender',
      authority: { public_id: 'a1', name: 'Община Тест' },
      tender: { public_id: 't1', title: 'Доставка на техника' },
    },
    title: 'Тестово заглавие за надценяване',
    explanation_bg: 'Неутрално обяснение защо случаят е съмнителен.',
    evidence: [
      { label: 'Стойност', value: 1000, money: { amount: 1000, currency: 'BGN', vat_included: true } },
    ],
    sources: [
      { url: 'https://registar.example/doc/1', label: 'Профил на купувача', fetched_at: '2026-06-01T12:00:00Z' },
    ],
    detected_at: '2026-06-01T12:00:00Z',
    published_at: '2026-06-01T12:00:00Z',
    ...overrides,
  };
}

describe('AppFlagPostCard', () => {
  it('renders headline, the TL;DR gist, severity + type badges, and a validated source', () => {
    renderWithProviders(<AppFlagPostCard flag={makeFlag()} />);

    expect(
      screen.getByRole('heading', { name: 'Тестово заглавие за надценяване' }),
    ).toBeInTheDocument();
    // The card shows a derived „Накратко" gist (post:tldrByType.*), not the full explanation_bg —
    // the full write-up + evidence live on the post page.
    expect(screen.getByText(/повече за същата стока/)).toBeInTheDocument();
    expect(screen.getByText('Високо')).toBeInTheDocument(); // severity: high
    expect(screen.getByText('Надценяване')).toBeInTheDocument(); // type: price_discrepancy

    const sourceLink = screen.getByRole('link', { name: /Профил на купувача/ });
    expect(sourceLink).toHaveAttribute('rel', 'noopener noreferrer');
  });

  it('shows the missing-source warning when a flag has no sources', () => {
    renderWithProviders(<AppFlagPostCard flag={makeFlag({ sources: [] })} />);
    expect(screen.getByText('Липсва източник')).toBeInTheDocument();
  });
});
