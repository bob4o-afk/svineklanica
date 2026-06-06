import { screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { renderWithProviders } from '@/test/renderWithProviders';
import { AdminLoginView } from './AdminLoginView';

describe('AdminLoginView', () => {
  it('renders the labelled login form for an anonymous visitor', () => {
    renderWithProviders(<AdminLoginView />, { routerEntries: ['/admin/login'] });
    expect(screen.getByRole('heading', { name: 'Вход за редактори' })).toBeInTheDocument();
    // `required` appends an asterisk to the label, so match the field names by substring.
    expect(screen.getByLabelText(/Имейл/)).toBeInTheDocument();
    expect(screen.getByLabelText(/Парола/)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Влез' })).toBeInTheDocument();
  });
});
