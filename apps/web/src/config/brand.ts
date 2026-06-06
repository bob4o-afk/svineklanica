/** The single source of truth for the product's display identity.
 *  Renaming the brand is a one-line change here. */
export const BRAND = {
  name: 'CORRUPTION FUCKER',
  short: 'CF',
  tagline: 'корупцията на показ',
  repoUrl: 'https://github.com/BabyNejii/corruption-fucker',
} as const;

export type Brand = typeof BRAND;
