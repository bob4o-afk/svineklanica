import { screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { renderWithProviders } from '@/test/renderWithProviders';
import { AppTldr } from './AppTldr';

describe('AppTldr', () => {
  it('renders the „Накратко" label and the given gist', () => {
    const gist = 'Платено 6× повече за същата стока — 2 216 916 лв.';
    renderWithProviders(<AppTldr text={gist} />);
    expect(screen.getByText('Накратко')).toBeInTheDocument();
    expect(screen.getByText(gist)).toBeInTheDocument();
  });
});
