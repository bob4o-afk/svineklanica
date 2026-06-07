import { http, HttpResponse } from 'msw';
import { regionName } from '@/lib/regions';
import type {
  CorruptionTax,
  CorruptionTaxCase,
  FlagPost,
  FlagSeverity,
  FlagType,
  MoneyAmount,
  Paginated,
  ProcurementSector,
  RegionAggregate,
} from '@/types/api';
import {
  authorities,
  companies,
  priceSeriesByKey,
  serialWinnerGraphById,
  tenders,
} from '../fixtures/data';
import * as store from '../fixtures/store';
import { adminHandlers } from './admin';

const SEVERITY_RANK: Record<FlagSeverity, number> = { critical: 4, high: 3, medium: 2, low: 1 };

function paginate<T>(items: T[], page: number, perPage: number): Paginated<T> {
  const start = (page - 1) * perPage;
  return { data: items.slice(start, start + perPage), page, per_page: perPage, total: items.length };
}

// Canonical feed order (mirrors EloquentPresentationRepository): latest first, then by
// severity (score), finally by views — each key fully tie-breaks the next.
function byNewest(a: FlagPost, b: FlagPost): number {
  const date = b.detected_at.localeCompare(a.detected_at);
  if (date !== 0) return date;
  const sev = SEVERITY_RANK[b.severity] - SEVERITY_RANK[a.severity];
  if (sev !== 0) return sev;
  return (b.view_count ?? 0) - (a.view_count ?? 0);
}

function bySeverity(a: FlagPost, b: FlagPost): number {
  const diff = SEVERITY_RANK[b.severity] - SEVERITY_RANK[a.severity];
  return diff !== 0 ? diff : byNewest(a, b);
}

function byViews(a: FlagPost, b: FlagPost): number {
  const diff = (b.view_count ?? 0) - (a.view_count ?? 0);
  return diff !== 0 ? diff : byNewest(a, b);
}

function countCritical(flags: FlagPost[]): number {
  return flags.filter((f) => f.severity === 'critical').length;
}

export const handlers = [
  http.get('/api/flag-posts', ({ request }) => {
    const url = new URL(request.url);
    const sort = url.searchParams.get('sort');
    const types = url.searchParams.getAll('type') as FlagType[];
    const categories = url.searchParams.getAll('category') as ProcurementSector[];
    const severities = url.searchParams.getAll('severity') as FlagSeverity[];
    const region = url.searchParams.get('region');
    const q = url.searchParams.get('q')?.toLowerCase() ?? '';
    const page = Math.max(1, Number(url.searchParams.get('page') ?? '1') || 1);
    const perPage = Math.min(50, Math.max(1, Number(url.searchParams.get('per_page') ?? '6') || 6));

    let items = store.approvedFlagList();
    if (types.length > 0) items = items.filter((f) => types.includes(f.type));
    if (categories.length > 0)
      items = items.filter((f) => f.category !== undefined && categories.includes(f.category));
    if (severities.length > 0) items = items.filter((f) => severities.includes(f.severity));
    if (region) items = items.filter((f) => f.subject.authority?.region_code === region);
    if (q) {
      items = items.filter(
        (f) => (f.title ?? '').toLowerCase().includes(q) || f.explanation_bg.toLowerCase().includes(q),
      );
    }
    items.sort(sort === 'severity' ? bySeverity : sort === 'views' ? byViews : byNewest);

    return HttpResponse.json(paginate(items, page, perPage));
  }),

  http.get('/api/flag-posts/:publicId', ({ params }) => {
    const flag = store.approvedFlagList().find((f) => f.public_id === params.publicId);
    if (!flag) return new HttpResponse(null, { status: 404 });
    return HttpResponse.json(flag);
  }),

  http.get('/api/authorities/:publicId', ({ params }) => {
    const authority = authorities.find((a) => a.public_id === params.publicId);
    if (!authority) return new HttpResponse(null, { status: 404 });
    const flags = store
      .approvedFlagList()
      .filter((f) => f.subject.authority?.public_id === authority.public_id);
    return HttpResponse.json({
      authority,
      stats: { flag_count: flags.length, critical_count: countCritical(flags) },
      flags: paginate(flags, 1, 20),
    });
  }),

  http.get('/api/companies/:eik', ({ params }) => {
    const company = companies.find((c) => c.eik === params.eik);
    if (!company) return new HttpResponse(null, { status: 404 });
    const flags = store
      .approvedFlagList()
      .filter((f) => f.subject.company?.public_id === company.public_id);
    return HttpResponse.json({
      company,
      stats: { flag_count: flags.length, critical_count: countCritical(flags) },
      related: companies.filter((c) => c.public_id !== company.public_id).slice(0, 2),
      flags: paginate(flags, 1, 20),
    });
  }),

  http.get('/api/price-series/:key', ({ params }) => {
    const series = priceSeriesByKey[String(params.key)];
    if (!series) return new HttpResponse(null, { status: 404 });
    return HttpResponse.json(series);
  }),

  http.get('/api/graphs/serial-winner/:publicId', ({ params }) => {
    // Unknown ids return an empty graph (friendly "no network" state) rather than a 404.
    const graph = serialWinnerGraphById[String(params.publicId)] ?? { nodes: [], edges: [] };
    return HttpResponse.json(graph);
  }),

  http.get('/api/regions/aggregate', ({ request }) => {
    const category = new URL(request.url).searchParams.get('category');
    const counts = new Map<string, number>();
    for (const flag of store.approvedFlagList()) {
      const code = flag.subject.authority?.region_code;
      if (code === undefined) continue;
      if (category !== null && flag.category !== category) continue;
      counts.set(code, (counts.get(code) ?? 0) + 1);
    }
    const aggregates: RegionAggregate[] = Array.from(counts, ([region_code, count]) => ({
      region_code,
      region_name: regionName(region_code),
      metric: count,
      flag_count: count,
    }));
    return HttpResponse.json(aggregates);
  }),

  http.get('/api/insights/corruption-tax', ({ request }) => {
    const taxes = Math.max(0, Number(new URL(request.url).searchParams.get('taxes_paid') ?? '0') || 0);
    const flags = store.approvedFlagList().slice(0, 5);
    const TOTAL = 11_000_000; // mock total public spend
    const baseAmounts = [1_200_000, 980_000, 640_000, 420_000, 350_000];
    const scoreOf: Record<FlagSeverity, number> = { critical: 91, high: 72, medium: 50, low: 30 };
    const money = (amount: number): MoneyAmount => ({ amount, currency: 'BGN', vat_included: true });
    const round2 = (n: number): number => Math.round(n * 100) / 100;

    const topCases: CorruptionTaxCase[] = flags.map((f, i) => {
      const amount = baseAmounts[i] ?? 200_000;
      const score = scoreOf[f.severity];
      const weight = score / 100;
      return {
        kind: 'tender',
        title: f.title ?? f.subject.tender?.title ?? 'Обществена поръчка',
        amount: money(amount),
        score,
        source_url: f.sources[0]?.url ?? 'https://ted.europa.eu/',
        user_share: money(round2((taxes * amount * weight) / TOTAL)),
        flag_public_id: f.public_id,
      };
    });

    const flagged = topCases.reduce((sum, c) => sum + c.amount.amount * (c.score / 100), 0);
    const rate = TOTAL > 0 ? flagged / TOTAL : 0;
    const result: CorruptionTax = {
      taxes_paid: money(taxes),
      corruption_rate: Math.round(rate * 10000) / 10000,
      user_corruption_amount: money(round2(taxes * rate)),
      total_spend: money(TOTAL),
      flagged_spend: money(round2(flagged)),
      per_sphere: [],
      top_cases: topCases,
    };
    return HttpResponse.json(result);
  }),

  http.get('/api/search', ({ request }) => {
    const q = new URL(request.url).searchParams.get('q')?.toLowerCase() ?? '';
    const match = (s: string): boolean => s.toLowerCase().includes(q);
    return HttpResponse.json({
      authorities: q ? authorities.filter((a) => match(a.name)) : [],
      companies: q ? companies.filter((c) => match(c.name) || c.eik.includes(q)) : [],
      tenders: q ? tenders.filter((t) => match(t.title)) : [],
    });
  }),

  ...adminHandlers,
];
