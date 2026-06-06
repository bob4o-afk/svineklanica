import { http, HttpResponse } from 'msw';
import type { FlagPost, Paginated, ReviewDecision, Source } from '@/types/api';
import type { SourceInput } from '../fixtures/store';
import * as store from '../fixtures/store';

/** 401 when no mock session, else null so the handler proceeds. */
function denyIfAnonymous(): Response | null {
  return store.currentUser() === null ? new HttpResponse(null, { status: 401 }) : null;
}

function paginate<T>(items: T[], page: number, perPage: number): Paginated<T> {
  const start = (page - 1) * perPage;
  return { data: items.slice(start, start + perPage), page, per_page: perPage, total: items.length };
}

/** Mock of the Sanctum SPA-cookie admin surface. The real backend enforces a real session +
 *  policies (security.md §1); here a module-level boolean stands in for the session. */
export const adminHandlers = [
  // Sanctum CSRF priming — real backend sets the XSRF-TOKEN cookie; the mock just 204s.
  http.get('/sanctum/csrf-cookie', () => new HttpResponse(null, { status: 204 })),

  http.post('/api/admin/login', async ({ request }) => {
    const { email, password } = (await request.json()) as { email?: string; password?: string };
    const user = store.tryLogin(email ?? '', password ?? '');
    if (user === null) return new HttpResponse(null, { status: 422 });
    return HttpResponse.json(user);
  }),

  http.post('/api/admin/logout', () => {
    store.logout();
    return new HttpResponse(null, { status: 204 });
  }),

  http.get('/api/admin/me', () => {
    const user = store.currentUser();
    if (user === null) return new HttpResponse(null, { status: 401 });
    return HttpResponse.json(user);
  }),

  // Review queue: defaults to pending; `status=approved|rejected|...` filters otherwise.
  http.get('/api/admin/flag-posts', ({ request }) => {
    const denied = denyIfAnonymous();
    if (denied) return denied;
    const url = new URL(request.url);
    const status = url.searchParams.get('status') ?? 'pending';
    const page = Math.max(1, Number(url.searchParams.get('page') ?? '1') || 1);
    const perPage = Math.min(50, Math.max(1, Number(url.searchParams.get('per_page') ?? '20') || 20));
    const items: FlagPost[] =
      status === 'pending' ? store.pendingFlagList() : store.approvedFlagList();
    return HttpResponse.json(paginate(items, page, perPage));
  }),

  // Admin detail can see ANY status (the public detail only serves approved).
  http.get('/api/admin/flag-posts/:publicId', ({ params }) => {
    const denied = denyIfAnonymous();
    if (denied) return denied;
    const flag = store.findFlag(String(params.publicId));
    if (flag === null) return new HttpResponse(null, { status: 404 });
    return HttpResponse.json(flag);
  }),

  http.post('/api/admin/flag-posts/:publicId/approve', async ({ params, request }) => {
    const denied = denyIfAnonymous();
    if (denied) return denied;
    const decision = (await request.json()) as ReviewDecision;
    const flag = store.approveFlag(String(params.publicId), decision);
    if (flag === null) return new HttpResponse(null, { status: 404 });
    return HttpResponse.json(flag);
  }),

  http.post('/api/admin/flag-posts/:publicId/reject', ({ params }) => {
    const denied = denyIfAnonymous();
    if (denied) return denied;
    const flag = store.rejectFlag(String(params.publicId));
    if (flag === null) return new HttpResponse(null, { status: 404 });
    return HttpResponse.json(flag);
  }),

  http.get('/api/admin/sources', () => {
    const denied = denyIfAnonymous();
    if (denied) return denied;
    return HttpResponse.json(store.sourceList());
  }),

  http.post('/api/admin/sources', async ({ request }) => {
    const denied = denyIfAnonymous();
    if (denied) return denied;
    const input = (await request.json()) as SourceInput;
    return HttpResponse.json(store.createSource(input), { status: 201 });
  }),

  http.patch('/api/admin/sources/:publicId', async ({ params, request }) => {
    const denied = denyIfAnonymous();
    if (denied) return denied;
    const patch = (await request.json()) as Partial<SourceInput>;
    const source: Source | null = store.updateSource(String(params.publicId), patch);
    if (source === null) return new HttpResponse(null, { status: 404 });
    return HttpResponse.json(source);
  }),

  http.delete('/api/admin/sources/:publicId', ({ params }) => {
    const denied = denyIfAnonymous();
    if (denied) return denied;
    const removed = store.deleteSource(String(params.publicId));
    if (!removed) return new HttpResponse(null, { status: 404 });
    return new HttpResponse(null, { status: 204 });
  }),
];
