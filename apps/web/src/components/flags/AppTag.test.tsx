import { screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { renderWithProviders } from '@/test/renderWithProviders';
import { AppTag } from './AppTag';

describe('AppTag', () => {
  it('renders the Bulgarian punk-tag label', () => {
    renderWithProviders(<AppTag tag="theft" />);
    expect(screen.getByText('Крадене на пари')).toBeInTheDocument();
  });
});
