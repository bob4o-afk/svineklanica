import { useEffect, useState } from 'react';

/** Phone-width breakpoint in px — mirrors MUI's `sm` (600), so it lines up with the
 *  `xs`/`sm` `sx` breakpoints used across the layout. */
const MOBILE_BREAKPOINT_PX = 600;

/** `true` on phone widths. Reactive (updates on resize / orientation change) and cache-safe,
 *  unlike server-side User-Agent sniffing. The one place "is this mobile?" is answered —
 *  components consume the boolean instead of repeating `matchMedia` (leha convention). */
export function useIsMobile(): boolean {
  const [isMobile, setIsMobile] = useState(false);

  useEffect(() => {
    const mq = window.matchMedia(`(max-width: ${MOBILE_BREAKPOINT_PX - 0.05}px)`);
    const update = () => setIsMobile(mq.matches);
    update();
    mq.addEventListener('change', update);
    return () => mq.removeEventListener('change', update);
  }, []);

  return isMobile;
}
