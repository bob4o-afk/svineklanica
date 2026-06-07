import { Helmet } from 'react-helmet-async';
import { BRAND } from '@/config/brand';

export interface AppSeoProps {
  /** Page title; composed as `"<title> — <BRAND>"`. Omit on the home page for the bare brand. */
  title?: string;
  /** Meta description / OG description for the page. Falls back to the site-wide description. */
  description?: string;
  /** Extra search keywords for this page (merged with the site-wide set). Bulgarian-first. */
  keywords?: string[];
  /** OG/Twitter preview image (absolute path from the site root). Defaults to the brand icon. */
  image?: string;
  /** OG type — `website` (default) for browse pages, `article` for a single post. */
  type?: 'website' | 'article';
  /** Emit `robots: noindex, nofollow` (admin + error pages — security.md: admin noindex). */
  noindex?: boolean;
}

/** Site-wide default description — punchy, in Bulgarian, leads with the hero line so the phrase
 *  "Парите са обществени. Прозрачността — не." surfaces the site in search + link previews. */
const DEFAULT_DESCRIPTION =
  'Парите са обществени. Прозрачността — не. Автоматично откриваме съмнителни обществени поръчки и плащания в България и показваме къде отиват парите ти — с връзка към първоизточника за всяко твърдение.';

/** Always-on keywords every page carries; per-page `keywords` are appended. Broad on purpose —
 *  the spheres, institutions and the everyday phrases people actually search, so the site can be
 *  found however someone looks for "where does my money go". */
const BASE_KEYWORDS = [
  'обществени поръчки',
  'обществени поръчки България',
  'прозрачност',
  'корупция',
  'корупция в България',
  'нагласени обществени поръчки',
  'злоупотреба с обществени средства',
  'къде ми отиват парите',
  'къде отиват парите',
  'харчене на държавата',
  'разходи на държавата',
  'обществени средства',
  'данъци',
  'търгове',
  'обществен контрол',
  'контрол на властта',
  'разследване корупция',
  'министерства',
  'общини',
  'здравеопазване',
  'съдебна система',
  'полиция',
  'ЕОП',
  'АОП',
  'Свинекланица',
];

const DEFAULT_IMAGE = '/pwa-512x512.png';

/** Per-route document head, managed via react-helmet-async. One wrapper so every page sets its
 *  title/description/keywords/canonical/OpenGraph/Twitter/robots the same way; the static fallback
 *  (for crawlers that don't run JS, e.g. social link scrapers) lives in index.html. */
export function AppSeo({
  title,
  description,
  keywords,
  image,
  type = 'website',
  noindex = false,
}: AppSeoProps) {
  const fullTitle =
    title !== undefined && title !== ''
      ? `${title} — ${BRAND.name}`
      : `${BRAND.name} — ${BRAND.tagline}`;

  const desc = description !== undefined && description !== '' ? description : DEFAULT_DESCRIPTION;
  const allKeywords = [...new Set([...(keywords ?? []), ...BASE_KEYWORDS])].join(', ');

  // Absolute canonical + preview-image URLs (crawlers prefer absolute). Built from the live origin
  // so they're correct on localhost, the demo box, and prod without a baked-in domain.
  const origin = typeof window !== 'undefined' ? window.location.origin : '';
  const path = typeof window !== 'undefined' ? window.location.pathname : '/';
  const canonical = `${origin}${path}`;
  const imageUrl = `${origin}${image ?? DEFAULT_IMAGE}`;

  return (
    <Helmet>
      <html lang="bg" />
      <title>{fullTitle}</title>
      <meta name="description" content={desc} />
      <meta name="keywords" content={allKeywords} />
      <meta name="author" content={BRAND.name} />
      <link rel="canonical" href={canonical} />
      <meta name="robots" content={noindex ? 'noindex, nofollow' : 'index, follow'} />

      {/* Open Graph (Facebook / Telegram / LinkedIn / Viber link previews) */}
      <meta property="og:site_name" content={BRAND.name} />
      <meta property="og:locale" content="bg_BG" />
      <meta property="og:type" content={type} />
      <meta property="og:title" content={fullTitle} />
      <meta property="og:description" content={desc} />
      <meta property="og:url" content={canonical} />
      <meta property="og:image" content={imageUrl} />
      <meta property="og:image:type" content="image/png" />
      <meta property="og:image:width" content="512" />
      <meta property="og:image:height" content="512" />
      <meta property="og:image:alt" content={`${BRAND.name} — ${BRAND.tagline}`} />

      {/* Official social profiles (Instagram + GitHub) — related links for crawlers/previews. */}
      <meta property="og:see_also" content={BRAND.socials.instagram} />
      <meta property="og:see_also" content={BRAND.socials.github} />
      <link rel="me" href={BRAND.socials.instagram} />
      <link rel="me" href={BRAND.socials.github} />
    </Helmet>
  );
}
