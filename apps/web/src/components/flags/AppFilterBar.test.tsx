import { fireEvent, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { renderWithProviders } from '@/test/renderWithProviders';
import { AppFilterBar } from './AppFilterBar';

describe('AppFilterBar', () => {
  it('toggles a severity facet on click', () => {
    const onChange = vi.fn();
    renderWithProviders(
      <AppFilterBar value={{ type: [], category: [], severity: [] }} onChange={onChange} />,
    );

    fireEvent.click(screen.getByText('Критично'));
    expect(onChange).toHaveBeenCalledWith({ type: [], category: [], severity: ['critical'] });
  });

  it('toggles a sector facet on click', () => {
    const onChange = vi.fn();
    renderWithProviders(
      <AppFilterBar value={{ type: [], category: [], severity: [] }} onChange={onChange} />,
    );

    fireEvent.click(screen.getByText('Здравеопазване'));
    expect(onChange).toHaveBeenCalledWith({ type: [], category: ['health'], severity: [] });
  });

  it('shows a clear control once a facet is active', () => {
    renderWithProviders(
      <AppFilterBar value={{ type: ['serial_winner'], category: [], severity: [] }} onChange={vi.fn()} />,
    );
    expect(screen.getByRole('button', { name: 'Изчисти филтрите' })).toBeInTheDocument();
  });
});
