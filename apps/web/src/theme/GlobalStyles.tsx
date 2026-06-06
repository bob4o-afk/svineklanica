import '@fontsource/inter/400.css';
import '@fontsource/inter/500.css';
import '@fontsource/inter/700.css';
import '@fontsource/jetbrains-mono/400.css';
import '@fontsource/jetbrains-mono/700.css';
import '@fontsource/manrope/700.css';
import '@fontsource/manrope/800.css';
import { GlobalStyles as MuiGlobalStyles } from '@mui/material';

// SVG feTurbulence noise — URL-encoded, no Cyrillic so no encoding issues
const GRAIN_SVG =
  "data:image/svg+xml,%3Csvg viewBox='0 0 512 512' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='g'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.72' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23g)'/%3E%3C/svg%3E";

/** Self-hosted fonts + base global rules. Grain is injected here via Emotion (reliable),
 *  not in punk.css where PostCSS drops complex data-URL rules on body pseudo-elements.
 *  The scrollbar lives here too so it can be theme-aware (light + dark). */
export function GlobalStyles() {
  return (
    <MuiGlobalStyles
      styles={(theme) => {
        const thumb =
          theme.palette.mode === 'dark' ? 'rgba(244, 241, 234, 0.26)' : 'rgba(10, 10, 10, 0.30)';
        return {
          'html, body, #root': { height: '100%' },
          // `overflow-x: clip` kills the sideways shift on mobile caused by the full-bleed
          // fixed decorations (watermark at 200% width, the diagonal `body::after` line) without
          // establishing a scroll container — so `position: sticky` (header + hero) keeps working.
          'html, body': { maxWidth: '100%', overflowX: 'clip' },
          body: { margin: 0 },
          '*:focus-visible': {
            outline: `2px solid ${theme.palette.primary.main}`,
            outlineOffset: 2,
          },
          // Custom scrollbar — rounded, transparent track, alarm-red on hover; themed per mode.
          html: {
            scrollbarWidth: 'thin',
            scrollbarColor: `${thumb} transparent`,
          },
          '*::-webkit-scrollbar': { width: '12px', height: '12px' },
          '*::-webkit-scrollbar-track': { backgroundColor: 'transparent' },
          '*::-webkit-scrollbar-thumb': {
            backgroundColor: thumb,
            borderRadius: '999px',
            border: '3px solid transparent',
            backgroundClip: 'padding-box',
          },
          // Fatten slightly + turn alarm-red on hover for a tactile, polished feel.
          '*::-webkit-scrollbar-thumb:hover': {
            backgroundColor: theme.palette.error.main,
            borderWidth: '2px',
          },
          // Film grain: SVG turbulence noise over every surface, 3% opacity, overlay blend.
          // z-index 9998 keeps it above all content but under MUI modals/tooltips (z: 9999+).
          'body::before': {
            content: '""',
            position: 'fixed',
            inset: 0,
            pointerEvents: 'none',
            zIndex: 9998,
            opacity: 0.03,
            backgroundImage: `url("${GRAIN_SVG}")`,
            backgroundRepeat: 'repeat',
            backgroundSize: '256px 256px',
            mixBlendMode: 'overlay',
          },
        };
      }}
    />
  );
}
