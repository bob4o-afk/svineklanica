import { useEffect, useState } from 'react';

/** Window scroll position mapped to a 0→1 progress value over the first `viewportRatio`
 *  of viewport height. 0 = top of page, 1 = scrolled `viewportRatio * innerHeight` or more.
 *  rAF-throttled and resize-aware so it stays cheap and correct across breakpoints. Used to
 *  drive the homepage hero shrink/reveal animation (frontend.md §10 — flagship views). */
export function useScrollProgress(viewportRatio = 0.7): number {
  const [progress, setProgress] = useState(0);

  useEffect(() => {
    let raf = 0;
    const compute = () => {
      const distance = Math.max(window.innerHeight * viewportRatio, 1);
      const next = Math.min(Math.max(window.scrollY / distance, 0), 1);
      setProgress(next);
    };
    const onScroll = () => {
      cancelAnimationFrame(raf);
      raf = requestAnimationFrame(compute);
    };
    compute();
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    return () => {
      window.removeEventListener('scroll', onScroll);
      window.removeEventListener('resize', onScroll);
      cancelAnimationFrame(raf);
    };
  }, [viewportRatio]);

  return progress;
}
