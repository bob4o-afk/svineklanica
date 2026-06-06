import { format, formatDistanceToNow, parseISO } from 'date-fns';
import { bg } from 'date-fns/locale';

/** Locale-aware date helpers. Never call `new Date(iso).toLocale*` directly (tz drift). */

export function formatDate(iso: string): string {
  return format(parseISO(iso), 'd MMM yyyy', { locale: bg });
}

export function formatDateTime(iso: string): string {
  return format(parseISO(iso), 'd MMM yyyy, HH:mm', { locale: bg });
}

export function formatRelative(iso: string): string {
  return formatDistanceToNow(parseISO(iso), { locale: bg, addSuffix: true });
}
