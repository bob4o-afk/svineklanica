import { useEffect, useRef, useState } from 'react';

const prefersReducedMotion = (): boolean =>
  typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const easeOutCubic = (progress: number): number => 1 - Math.pow(1 - progress, 3);

export interface AppCountUpProps {
  /** Target value to roll up to. */
  value: number;
  /** Formats the (rounded) number each frame — defaults to the raw integer. */
  format?: (n: number) => string;
  /** Roll duration in ms. */
  durationMs?: number;
}

/** Casino-style count-up: rolls from the previous value to `value` with eased timing, so a
 *  freshly-loaded stat spins up into place. Reduced-motion users get the final number at once.
 *  Renders text only — wrap it in whatever Typography you need (frontend.md §2). */
export function AppCountUp({ value, format = (n) => String(n), durationMs = 1100 }: AppCountUpProps) {
  const [display, setDisplay] = useState(0);
  const fromRef = useRef(0);
  const frameRef = useRef<number | null>(null);

  useEffect(() => {
    if (prefersReducedMotion()) {
      setDisplay(value);
      fromRef.current = value;
      return;
    }

    const from = fromRef.current;
    let startTs: number | null = null;
    const tick = (ts: number): void => {
      if (startTs === null) startTs = ts;
      const progress = durationMs <= 0 ? 1 : Math.min(1, (ts - startTs) / durationMs);
      setDisplay(from + (value - from) * easeOutCubic(progress));
      if (progress < 1) {
        frameRef.current = requestAnimationFrame(tick);
      } else {
        fromRef.current = value;
      }
    };
    frameRef.current = requestAnimationFrame(tick);

    return () => {
      if (frameRef.current !== null) cancelAnimationFrame(frameRef.current);
    };
  }, [value, durationMs]);

  return <>{format(Math.round(display))}</>;
}
