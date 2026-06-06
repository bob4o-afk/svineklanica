import type { ReactNode } from 'react';

/** Pass-through until Phase 5 wires privacy-friendly analytics (Plausible/Umami)
 *  behind VITE_ANALYTICS_* env flags. No cookies, no tracking by default. */
export function AnalyticsProvider({ children }: { children: ReactNode }) {
  return <>{children}</>;
}
