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
 *  not in punk.css where PostCSS drops complex data-URL rules on body pseudo-elements. */
export function GlobalStyles() {
  return (
    <MuiGlobalStyles
      styles={(theme) => ({
        'html, body, #root': { height: '100%' },
        body: { margin: 0 },
        '*:focus-visible': {
          outline: `2px solid ${theme.palette.primary.main}`,
          outlineOffset: 2,
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
      })}
    />
  );
}
