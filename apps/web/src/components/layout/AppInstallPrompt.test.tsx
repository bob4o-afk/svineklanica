import { act, fireEvent, screen, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { renderWithProviders } from '@/test/renderWithProviders';
import { AppInstallPrompt } from './AppInstallPrompt';

const ORIGINAL_UA = window.navigator.userAgent;
const IPHONE_UA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15';

function setUserAgent(ua: string): void {
  Object.defineProperty(window.navigator, 'userAgent', { value: ua, configurable: true });
}

interface MockPromptEvent extends Event {
  prompt: ReturnType<typeof vi.fn>;
  userChoice: Promise<{ outcome: 'accepted' | 'dismissed'; platform: string }>;
}

/** Dispatch a synthetic `beforeinstallprompt` like Chromium would, returning the event so the
 *  test can assert on its `prompt` spy. */
function fireBeforeInstallPrompt(outcome: 'accepted' | 'dismissed' = 'accepted'): MockPromptEvent {
  const event = new Event('beforeinstallprompt') as MockPromptEvent;
  event.prompt = vi.fn(() => Promise.resolve());
  Object.defineProperty(event, 'userChoice', { value: Promise.resolve({ outcome, platform: 'web' }) });
  act(() => {
    window.dispatchEvent(event);
  });
  return event;
}

describe('AppInstallPrompt', () => {
  beforeEach(() => {
    localStorage.clear();
  });
  afterEach(() => {
    localStorage.clear();
    setUserAgent(ORIGINAL_UA);
  });

  it('renders nothing on a desktop browser with no install offer yet', () => {
    renderWithProviders(<AppInstallPrompt />);
    expect(screen.queryByText('Инсталирай Свинекланица')).not.toBeInTheDocument();
  });

  it('shows the install banner when the browser fires beforeinstallprompt', () => {
    renderWithProviders(<AppInstallPrompt />);
    fireBeforeInstallPrompt();
    expect(screen.getByText('Инсталирай Свинекланица')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Инсталирай' })).toBeInTheDocument();
  });

  it('runs the native prompt and hides the banner on install', async () => {
    renderWithProviders(<AppInstallPrompt />);
    const event = fireBeforeInstallPrompt('accepted');
    fireEvent.click(screen.getByRole('button', { name: 'Инсталирай' }));
    expect(event.prompt).toHaveBeenCalledOnce();
    await waitFor(() =>
      expect(screen.queryByText('Инсталирай Свинекланица')).not.toBeInTheDocument(),
    );
    expect(localStorage.getItem('pwa-install-dismissed')).toBe('1');
  });

  it('dismiss hides the banner and remembers the choice', () => {
    renderWithProviders(<AppInstallPrompt />);
    fireBeforeInstallPrompt();
    fireEvent.click(screen.getByRole('button', { name: 'Затвори' }));
    expect(screen.queryByText('Инсталирай Свинекланица')).not.toBeInTheDocument();
    expect(localStorage.getItem('pwa-install-dismissed')).toBe('1');
  });

  it('shows the iOS Add-to-Home-Screen hint (no install button) on iOS', () => {
    setUserAgent(IPHONE_UA);
    renderWithProviders(<AppInstallPrompt />);
    expect(screen.getByText('Инсталирай Свинекланица')).toBeInTheDocument();
    expect(screen.getByText(/Сподели/)).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Инсталирай' })).not.toBeInTheDocument();
  });

  it('stays hidden once the user has dismissed it', () => {
    localStorage.setItem('pwa-install-dismissed', '1');
    renderWithProviders(<AppInstallPrompt />);
    fireBeforeInstallPrompt();
    expect(screen.queryByText('Инсталирай Свинекланица')).not.toBeInTheDocument();
  });
});
