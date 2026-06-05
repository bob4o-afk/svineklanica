/** Central route table. English paths (URLs are infrastructure); UI text is Bulgarian.
 *  Builders `encodeURIComponent` their dynamic segments so untrusted ids/keys can't break
 *  out of the path (security.md — input validation at every boundary). */

function seg(value: string): string {
  return encodeURIComponent(value);
}

export const paths = {
  home: '/',
  feed: '/feed',
  post: (publicId: string) => `/posts/${seg(publicId)}`,
  authority: (publicId: string) => `/authorities/${seg(publicId)}`,
  company: (eik: string) => `/companies/${seg(eik)}`,
  price: (seriesKey: string) => `/price/${seg(seriesKey)}`,
  network: (publicId: string) => `/network/${seg(publicId)}`,
  map: '/map',
  about: '/about',
  adminLogin: '/admin/login',
  admin: '/admin',
  adminPending: '/admin/pending',
  adminReview: (publicId: string) => `/admin/review/${seg(publicId)}`,
  adminSources: '/admin/sources',
} as const;

/** Route patterns (with `:param`) for the router definition — distinct from the builders above. */
export const patterns = {
  post: '/posts/:publicId',
  authority: '/authorities/:publicId',
  company: '/companies/:eik',
  price: '/price/:seriesKey',
  network: '/network/:publicId',
  adminReview: '/admin/review/:publicId',
} as const;
