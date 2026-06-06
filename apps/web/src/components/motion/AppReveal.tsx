import { Box, type BoxProps } from '@mui/material';
import { useEffect, useRef, useState } from 'react';

export interface AppRevealProps extends BoxProps {
  /** Stagger delay in ms before the reveal starts — use an index * step for lists. */
  delay?: number;
  /** Distance in px the content slides up into place. */
  distance?: number;
  /** Re-run the reveal every time it re-enters view. Default reveals once and stops observing. */
  once?: boolean;
}

const prefersReducedMotion = (): boolean =>
  typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/** Fades + slides its children up the first time they scroll into view (IntersectionObserver).
 *  Reduced-motion users get the content immediately with no transition. The one place we do
 *  scroll-reveal — wrap content in this instead of hand-rolling observers (frontend.md §2/§7). */
export function AppReveal({
  delay = 0,
  distance = 18,
  once = true,
  children,
  sx,
  ...rest
}: AppRevealProps) {
  const ref = useRef<HTMLDivElement>(null);
  const [shown, setShown] = useState(prefersReducedMotion);

  useEffect(() => {
    if (prefersReducedMotion()) {
      setShown(true);
      return;
    }
    const el = ref.current;
    if (el === null) return;
    const observer = new IntersectionObserver(
      (entries) => {
        for (const entry of entries) {
          if (entry.isIntersecting) {
            setShown(true);
            if (once) observer.disconnect();
          } else if (!once) {
            setShown(false);
          }
        }
      },
      // Fire a touch before fully in view so the motion reads as it arrives, not after.
      { threshold: 0.15, rootMargin: '0px 0px -8% 0px' },
    );
    observer.observe(el);
    return () => observer.disconnect();
  }, [once]);

  return (
    <Box
      ref={ref}
      sx={{
        opacity: shown ? 1 : 0,
        transform: shown ? 'none' : `translateY(${distance}px)`,
        transition: 'opacity 0.55s ease, transform 0.55s cubic-bezier(0.16, 1, 0.3, 1)',
        transitionDelay: `${delay}ms`,
        willChange: 'opacity, transform',
        ...sx,
      }}
      {...rest}
    >
      {children}
    </Box>
  );
}
