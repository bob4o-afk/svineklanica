import { fireEvent, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { renderWithProviders } from '@/test/renderWithProviders';
import { AppSearchInput } from './AppSearchInput';

describe('AppSearchInput', () => {
  it('shows the value and clears it via the clear button', () => {
    const onChange = vi.fn();
    renderWithProviders(<AppSearchInput value="път" onChange={onChange} />);

    expect(screen.getByDisplayValue('път')).toBeInTheDocument();
    fireEvent.click(screen.getByRole('button', { name: 'Изчисти' }));
    expect(onChange).toHaveBeenCalledWith('');
  });

  it('has no clear button when empty', () => {
    renderWithProviders(<AppSearchInput value="" onChange={vi.fn()} />);
    expect(screen.queryByRole('button', { name: 'Изчисти' })).toBeNull();
  });
});
