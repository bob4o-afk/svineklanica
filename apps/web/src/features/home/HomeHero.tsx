import { Box, Stack, Typography } from '@mui/material';
import { CaretDownIcon } from '@phosphor-icons/react';
import { useEffect, useRef } from 'react';
import { useTranslation } from 'react-i18next';
import { AppBrandLockup } from '@/components/brand/AppBrandLockup';
import { fonts } from '@/theme/typography';

/** The first screen: logo + name, sitting a little above centre at any width. It stays pinned
 *  (sticky) while you scroll — shrinking and drifting up — and the page content scrolls up over
 *  it. The animation is written straight to the DOM via refs in a rAF loop, so scrolling never
 *  re-renders the (heavier) content below it — that's what keeps it buttery (frontend.md §10). */
export function HomeHero() {
  const { t } = useTranslation();
  const lockupRef = useRef<HTMLDivElement>(null);
  const hintRef = useRef<HTMLDivElement>(null);
  const heroRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    let raf = 0;
    const apply = () => {
      const distance = Math.max(window.innerHeight * 0.72, 1);
      const raw = Math.min(Math.max(window.scrollY / distance, 0), 1);
      // easeOutQuad — most of the motion happens early then settles, which reads smoother than
      // a linear scrub.
      const eased = 1 - (1 - raw) * (1 - raw);
      const scale = 1 - 0.42 * eased; // 1 → 0.58
      const shift = -13 * eased; // vh — rises toward the header

      const lockup = lockupRef.current;
      if (lockup) {
        lockup.style.transform = `translate3d(0, ${shift}vh, 0) scale(${scale})`;
        lockup.style.opacity = String(Math.max(1 - raw * 1.25, 0));
      }
      if (hintRef.current) hintRef.current.style.opacity = String(Math.max(1 - raw * 5, 0));
      if (heroRef.current) heroRef.current.setAttribute('aria-hidden', raw > 0.9 ? 'true' : 'false');
    };
    const onScroll = () => {
      cancelAnimationFrame(raf);
      raf = requestAnimationFrame(apply);
    };
    apply();
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    return () => {
      window.removeEventListener('scroll', onScroll);
      window.removeEventListener('resize', onScroll);
      cancelAnimationFrame(raf);
    };
  }, []);

  return (
    <Box
      ref={heroRef}
      sx={{
        position: 'sticky',
        top: 0,
        zIndex: 0,
        height: '100svh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        textAlign: 'center',
        // Lift the centred lockup a little above dead-centre — feels more poised than mid-screen.
        pb: { xs: '14vh', sm: '12vh' },
        pointerEvents: 'none',
      }}
    >
      <Box ref={lockupRef} sx={{ willChange: 'transform, opacity' }}>
        <AppBrandLockup size="hero" nameComponent="h1" />
      </Box>

      {/* Scroll affordance — a gentle hint that there's more below, gone the moment you move. */}
      <Stack
        ref={hintRef}
        alignItems="center"
        spacing={0.5}
        sx={{ position: 'absolute', bottom: { xs: 24, sm: 40 }, color: 'text.secondary' }}
      >
        <Typography
          sx={{
            fontFamily: fonts.mono,
            fontWeight: 600,
            fontSize: '0.6rem',
            letterSpacing: '0.22em',
            textTransform: 'uppercase',
          }}
        >
          {t('home:hero.scrollHint')}
        </Typography>
        <Box sx={{ animation: 'app-hero-bounce 1.6s ease-in-out infinite', display: 'flex' }}>
          <CaretDownIcon size={18} weight="bold" />
        </Box>
      </Stack>
    </Box>
  );
}
