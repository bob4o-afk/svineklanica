import { fireEvent, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { renderWithProviders } from '@/test/renderWithProviders';
import { AppFilterBar } from './AppFilterBar';

describe('AppFilterBar', () => {
  it('toggles a severity facet on click', () => {
    const onChange = vi.fn();
    renderWithProviders(<AppFilterBar value={{ type: [], severity: [] }} onChange={onChange} />);

    fireEvent.click(screen.getByText('Критично'));
    expect(onChange).toHaveBeenCalledWith({ type: [], severity: ['critical'] });
  });

  it('shows a clear control once a facet is active', () => {
    renderWithProviders(
      <AppFilterBar value={{ type: ['serial_winner'], severity: [] }} onChange={vi.fn()} />,
    );
    expect(screen.getByRole('button', { name: 'Изчисти филтрите' })).toBeInTheDocument();
  });
});
