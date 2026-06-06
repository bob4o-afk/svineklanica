/**
 * Generate the PWA raster icons from `public/favicon.svg`.
 * Run with `pnpm gen:icons` (or `node scripts/generate-pwa-icons.mjs`).
 *
 * The generated PNGs are committed to `public/` so the build never depends on `sharp` at
 * runtime/CI. Re-run this only when the source logo changes.
 *
 * Output:
 *   pwa-192x192.png / pwa-512x512.png   — purpose "any" (keep the logo's rounded-rect bg)
 *   pwa-maskable-512x512.png            — purpose "maskable" (full-bleed ink, logo in safe zone)
 *   apple-touch-icon-180x180.png        — iOS home screen (opaque square; iOS rounds corners)
 */
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import sharp from 'sharp';

const here = dirname(fileURLToPath(import.meta.url));
const pub = join(here, '..', 'public');
const svg = readFileSync(join(pub, 'favicon.svg'));
const INK = '#0a0a0a'; // palette.ink — keep in sync with src/theme/tokens.ts

const DENSITY = 1024; // rasterize the SVG large, then downscale for crisp edges

function base(size) {
  return sharp(svg, { density: DENSITY }).resize(size, size, {
    fit: 'contain',
    background: { r: 0, g: 0, b: 0, alpha: 0 },
  });
}

async function main() {
  // purpose "any" — preserve the logo's own rounded-rect background + transparent corners
  await base(192).png().toFile(join(pub, 'pwa-192x192.png'));
  await base(512).png().toFile(join(pub, 'pwa-512x512.png'));

  // apple-touch — flatten onto ink so the corners are opaque (iOS applies its own mask)
  await sharp(svg, { density: DENSITY })
    .resize(180, 180)
    .flatten({ background: INK })
    .png()
    .toFile(join(pub, 'apple-touch-icon-180x180.png'));

  // maskable — full-bleed ink canvas with the logo inside the ~75% safe zone
  const inner = await sharp(svg, { density: DENSITY }).resize(384, 384).png().toBuffer();
  await sharp({ create: { width: 512, height: 512, channels: 4, background: INK } })
    .composite([{ input: inner, gravity: 'center' }])
    .png()
    .toFile(join(pub, 'pwa-maskable-512x512.png'));

  console.log('✓ PWA icons written to public/ (192, 512, maskable-512, apple-touch-180)');
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
