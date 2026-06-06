import '@testing-library/jest-dom/vitest';
import { afterAll, afterEach, beforeAll } from 'vitest';
import '@/i18n';
import { resetStore } from '@/mocks/fixtures/store';
import { server } from '@/mocks/server';

// jsdom has no matchMedia; ColorModeProvider reads it on mount. Use defineProperty so the
// descriptor's `value` is untyped (no MediaQueryList structural-cast gymnastics).
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: (query: string) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: () => {},
    removeListener: () => {},
    addEventListener: () => {},
    removeEventListener: () => {},
    dispatchEvent: () => false,
  }),
});

// MSW: real handlers, fail loudly on anything unhandled so tests never hit the network.
beforeAll(() => server.listen({ onUnhandledRequest: 'error' }));
afterEach(() => {
  server.resetHandlers();
  resetStore(); // restore the seed so mutating tests (approve/reject/sources) don't leak state
});
afterAll(() => server.close());
