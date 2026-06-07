import { describe, expect, it } from 'vitest';
import { http } from '@/lib/http';
import type { AdminUser, FlagPost, Paginated, Source } from '@/types/api';

const CREDS = { email: 'admin@example.com', password: 'parola' };

async function login(): Promise<void> {
  await http.post('/admin/login', CREDS);
}

describe('admin API seam (MSW)', () => {
  it('rejects anonymous callers and bad credentials, accepts the demo editor', async () => {
    await expect(http.get('/admin/me')).rejects.toHaveProperty('status', 401);
    await expect(http.get('/admin/flag-posts')).rejects.toHaveProperty('status', 401);
    await expect(
      http.post('/admin/login', { email: CREDS.email, password: 'wrong' }),
    ).rejects.toHaveProperty('status', 422);

    const ok = await http.post<AdminUser>('/admin/login', CREDS);
    expect(ok.data.isAdmin).toBe(true);
    const me = await http.get<AdminUser>('/admin/me');
    expect(me.data.email).toBe(CREDS.email);
  });

  it('approving a pending flag publishes it to the public feed with its punk tags', async () => {
    await login();
    const pending = await http.get<Paginated<FlagPost>>('/admin/flag-posts', {
      params: { status: 'pending', per_page: 50 },
    });
    expect(pending.data.total).toBeGreaterThan(0);
    const target = pending.data.data[0];
    if (target === undefined) throw new Error('expected a pending flag to review');

    const beforeFeed = await http.get<Paginated<FlagPost>>('/flag-posts', { params: { per_page: 50 } });
    await http.post(`/admin/flag-posts/${target.public_id}/approve`, { tags: ['theft'] });
    const afterFeed = await http.get<Paginated<FlagPost>>('/flag-posts', { params: { per_page: 50 } });

    expect(afterFeed.data.total).toBe(beforeFeed.data.total + 1);
    const published = afterFeed.data.data.find((flag) => flag.public_id === target.public_id);
    expect(published?.status).toBe('approved');
    expect(published?.tags).toContain('theft');
  });

  it('rejecting a pending flag keeps it out of the public feed', async () => {
    await login();
    const pending = await http.get<Paginated<FlagPost>>('/admin/flag-posts', {
      params: { status: 'pending', per_page: 50 },
    });
    const target = pending.data.data[0];
    if (target === undefined) throw new Error('expected a pending flag to reject');

    await http.post(`/admin/flag-posts/${target.public_id}/reject`, {});
    const feed = await http.get<Paginated<FlagPost>>('/flag-posts', { params: { per_page: 50 } });
    expect(feed.data.data.some((flag) => flag.public_id === target.public_id)).toBe(false);
  });

  it('sources CRUD: create, list, then delete', async () => {
    await login();
    const created = await http.post<Source>('/admin/sources', {
      key: 'demo',
      label: 'Демо източник',
      base_url: 'https://demo.example',
      enabled: true,
    });
    expect(created.data.public_id).toBeTruthy();

    const list = await http.get<Source[]>('/admin/sources');
    expect(list.data.some((source) => source.public_id === created.data.public_id)).toBe(true);

    await http.delete(`/admin/sources/${created.data.public_id}`);
    const after = await http.get<Source[]>('/admin/sources');
    expect(after.data.some((source) => source.public_id === created.data.public_id)).toBe(false);
  });
});
