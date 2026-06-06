import { http, HttpResponse } from 'msw';
import type { FlagPost, FlagSeverity, FlagType, Paginated, ProcurementSector } from '@/types/api';
import {
  approvedFlags,
  authorities,
  companies,
  priceSeriesByKey,
  regionAggregates,
  serialWinnerGraphById,
  tenders,
} from '../fixtures/data';

const SEVERITY_RANK: Record<FlagSeverity, number> = { critical: 4, high: 3, medium: 2, low: 1 };

function paginate<T>(items: T[], page: number, perPage: number): Paginated<T> {
  const start = (page - 1) * perPage;
  return { data: items.slice(start, start + perPage), page, per_page: perPage, total: items.length };
}

function byNewest(a: FlagPost, b: FlagPost): number {
  return b.detected_at.localeCompare(a.detected_at);
}

function bySeverity(a: FlagPost, b: FlagPost): number {
  const diff = SEVERITY_RANK[b.severity] - SEVERITY_RANK[a.severity];
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

    let items = approvedFlags.slice();
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
    items.sort(sort === 'severity' ? bySeverity : byNewest);

    return HttpResponse.json(paginate(items, page, perPage));
  }),

  http.get('/api/flag-posts/:publicId', ({ params }) => {
    const flag = approvedFlags.find((f) => f.public_id === params.publicId);
    if (!flag) return new HttpResponse(null, { status: 404 });
    return HttpResponse.json(flag);
  }),

  http.get('/api/authorities/:publicId', ({ params }) => {
    const authority = authorities.find((a) => a.public_id === params.publicId);
    if (!authority) return new HttpResponse(null, { status: 404 });
    const flags = approvedFlags.filter((f) => f.subject.authority?.public_id === authority.public_id);
    return HttpResponse.json({
      authority,
      stats: { flag_count: flags.length, critical_count: countCritical(flags) },
      flags: paginate(flags, 1, 20),
    });
  }),

  http.get('/api/companies/:eik', ({ params }) => {
    const company = companies.find((c) => c.eik === params.eik);
    if (!company) return new HttpResponse(null, { status: 404 });
    const flags = approvedFlags.filter((f) => f.subject.company?.public_id === company.public_id);
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

  http.get('/api/regions/aggregate', () => HttpResponse.json(regionAggregates)),

  http.get('/api/search', ({ request }) => {
    const q = new URL(request.url).searchParams.get('q')?.toLowerCase() ?? '';
    const match = (s: string): boolean => s.toLowerCase().includes(q);
    return HttpResponse.json({
      authorities: q ? authorities.filter((a) => match(a.name)) : [],
      companies: q ? companies.filter((c) => match(c.name) || c.eik.includes(q)) : [],
      tenders: q ? tenders.filter((t) => match(t.title)) : [],
    });
  }),

  // Admin (Phase 1): nobody is authenticated yet — full flow lands in Phase 4.
  http.get('/api/admin/me', () => new HttpResponse(null, { status: 401 })),
];
