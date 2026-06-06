/** The single source of truth for the product's display identity.
 *  Renaming the brand is a one-line change here. */
export const BRAND = {
  name: 'СВИНЕКЛАНИЦА',
  nameParts: ['СВИНЕ', 'КЛАНИЦА'] as const,
  short: 'СК',
  tagline: 'корупцията на показ',
  repoUrl: 'https://github.com/BabyNejii/corruption-fucker',
} as const;

export type Brand = typeof BRAND;
