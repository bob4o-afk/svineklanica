import { Helmet } from 'react-helmet-async';
import { BRAND } from '@/config/brand';

export interface AppSeoProps {
  /** Page title; composed as `"<title> — <BRAND>"`. Omit on the home page for the bare brand. */
  title?: string;
  /** Meta description / OG description for the page. */
  description?: string;
  /** Emit `robots: noindex, nofollow` (admin + error pages — security.md: admin noindex). */
  noindex?: boolean;
}

/** Per-route document head, managed via react-helmet-async. One wrapper so every page sets its
 *  title/description/robots the same way; the static fallback lives in index.html. */
export function AppSeo({ title, description, noindex = false }: AppSeoProps) {
  const fullTitle =
    title !== undefined && title !== ''
      ? `${title} — ${BRAND.name}`
      : `${BRAND.name} — ${BRAND.tagline}`;

  return (
    <Helmet>
      <title>{fullTitle}</title>
      <meta property="og:title" content={fullTitle} />
      <meta property="og:type" content="website" />
      {description !== undefined ? <meta name="description" content={description} /> : null}
      {description !== undefined ? <meta property="og:description" content={description} /> : null}
      {noindex ? <meta name="robots" content="noindex, nofollow" /> : null}
    </Helmet>
  );
}
