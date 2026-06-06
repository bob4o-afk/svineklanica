/**
 * Frontend-owned API contract — the canonical shapes the backend must match,
 * until `composer sync:api-types` ships `generated.d.ts`. The app imports API types
 * ONLY from `@/types/api` (which re-exports this now, generated later).
 * All optional fields use `field?: T` to satisfy `exactOptionalPropertyTypes`.
 */

export type FlagType =
  | 'price_discrepancy'
  | 'tailored_spec'
  | 'serial_winner'
  | 'cancelled'
  | 'implausible_scope'
  | 'delayed_payment'
  | 'doc_clone';

export type FlagSeverity = 'low' | 'medium' | 'high' | 'critical';
export type ApprovalStatus = 'detected' | 'pending' | 'approved' | 'rejected';
export type SubjectType = 'tender' | 'authority' | 'company';
export type Currency = 'BGN' | 'EUR';
export type FlagSort = 'newest' | 'severity';

/** Procurement sector (CPV-derived) — lets a citizen filter "show me the hospital/school deals". */
export type ProcurementSector =
  | 'health'
  | 'education'
  | 'roads'
  | 'construction'
  | 'it'
  | 'utilities'
  | 'supplies'
  | 'other';

/** Editorial "punk" tags (CLAUDE.md §1.0.1) — the savage plain-Bulgarian roast layer an admin
 *  assigns on publish, on TOP of the computed type/sector/severity. Display labels live in i18n. */
export type PunkTag = 'theft' | 'dodgy_deal' | 'shushi_mushi';

export interface SourceRef {
  url: string;
  label: string;
  fetched_at: string; // ISO-8601 UTC
}

export interface MoneyAmount {
  amount: number;
  currency: Currency;
  vat_included: boolean;
}

export interface AuthorityRef {
  public_id: string;
  name: string;
  region_code?: string;
}

export interface CompanyRef {
  public_id: string;
  eik: string;
  name: string;
}

export interface TenderRef {
  public_id: string;
  ted_id?: string;
  title: string;
  cpv_code?: string;
}

export interface FlagSubject {
  type: SubjectType;
  authority?: AuthorityRef;
  company?: CompanyRef;
  tender?: TenderRef;
}

export interface EvidenceItem {
  label: string;
  value: string | number;
  money?: MoneyAmount;
}

export interface FlagPost {
  public_id: string;
  type: FlagType;
  category?: ProcurementSector;
  severity: FlagSeverity;
  status: ApprovalStatus;
  subject: FlagSubject;
  title?: string;
  explanation_bg: string;
  evidence: EvidenceItem[];
  sources: SourceRef[]; // >= 1 (no source -> no flag)
  detected_at: string;
  published_at?: string;
  /** Present on price_discrepancy flags — links to the product's price-over-time series. */
  series_key?: string;
  /** Editorial punk tags assigned on publish (CLAUDE.md §1.0.1) — the roast on top of the data. */
  tags?: PunkTag[];
}

export interface Paginated<T> {
  data: T[];
  page: number;
  per_page: number;
  total: number;
}

export interface FlagFeedQuery {
  type?: FlagType[];
  category?: ProcurementSector[];
  severity?: FlagSeverity[];
  region?: string;
  cpv?: string;
  q?: string;
  sort?: FlagSort;
  page?: number;
  per_page?: number;
}

export interface EntityStats {
  flag_count: number;
  critical_count: number;
  total_value?: MoneyAmount;
}

export interface AuthorityDetail {
  authority: AuthorityRef;
  stats: EntityStats;
  flags: Paginated<FlagPost>;
}

export interface CompanyDetail {
  company: CompanyRef;
  stats: EntityStats;
  related: CompanyRef[];
  flags: Paginated<FlagPost>;
}

export interface PricePoint {
  captured_at: string;
  price: MoneyAmount;
  source: SourceRef;
  tender_ref?: TenderRef;
  vendor?: CompanyRef;
}

export interface PriceSeries {
  series_key: string;
  product_label: string;
  cpv_code?: string;
  unit?: string;
  points: PricePoint[];
}

export interface GraphNode {
  id: string;
  kind: 'company' | 'authority';
  label: string;
  public_id: string;
  eik?: string;
  win_count?: number;
  cluster_id?: string;
}

export interface GraphEdge {
  id: string;
  source: string;
  target: string;
  weight: number;
  label?: string;
}

export interface SerialWinnerGraph {
  nodes: GraphNode[];
  edges: GraphEdge[];
}

export interface RegionAggregate {
  region_code: string;
  region_name: string;
  metric: number;
  flag_count: number;
  total_value?: MoneyAmount;
}

export interface SearchResults {
  authorities: AuthorityRef[];
  companies: CompanyRef[];
  tenders: TenderRef[];
}

export type AdminRole = 'admin' | 'reviewer';

export interface AdminUser {
  public_id: string;
  name: string;
  email: string;
  role: AdminRole;
}

export interface Source {
  public_id: string;
  key: string;
  label: string;
  base_url: string;
  enabled: boolean;
  last_ingested_at?: string;
  notes?: string;
}

/** Admin review edits applied when approving/rejecting a flag-post. */
export interface ReviewDecision {
  title?: string;
  explanation_bg?: string;
  note?: string;
  /** Punk tags to attach on approval (CLAUDE.md §1.0.1). */
  tags?: PunkTag[];
}
