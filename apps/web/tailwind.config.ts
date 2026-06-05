import type { Config } from 'tailwindcss';

// Tailwind decorates; MUI owns the CSS baseline (frontend.md §1):
//  - preflight OFF so it doesn't fight MUI's reset
//  - important scoped to #root so utilities win over MUI when intentionally used
//
// This file is the SINGLE home for color/spacing tokens. Mirror these in the
// MUI theme. No hex literals anywhere else in the app.
const config: Config = {
  content: ['./index.html', './src/**/*.{ts,tsx}'],
  important: '#root',
  corePlugins: { preflight: false },
  theme: {
    extend: {
      colors: {
        // Punk palette — loud, high-contrast. Semantic names only.
        ink: '#0a0a0a', // near-black background
        bone: '#f4f1ea', // off-white text/surface
        alarm: '#ff2d2d', // danger / red flag
        acid: '#c6ff00', // accent / highlight
        rust: '#b5651d', // warning
        muted: '#6b7280', // secondary text
      },
      fontFamily: {
        mono: ['"JetBrains Mono"', 'ui-monospace', 'monospace'],
      },
    },
  },
  plugins: [],
};

export default config;
