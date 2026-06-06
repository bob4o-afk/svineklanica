import { Link as MuiLink, Typography } from '@mui/material';
import { ArrowSquareOutIcon, WarningIcon } from '@phosphor-icons/react';
import { useTranslation } from 'react-i18next';
import type { SourceRef } from '@/types/api';

/** SECURITY: source URLs come from scraped gov pages and are UNTRUSTED.
 *  Only http(s) is allowed (reject javascript:/data:/etc); we open in a new tab with
 *  rel="noopener noreferrer" and surface the hostname so a reader can see where a click
 *  actually goes. An invalid/missing source becomes a visible warning, never a silent link.
 *  (plan §Security · .claude/rules/security.md §4–§5) */
const SAFE_PROTOCOLS = new Set(['http:', 'https:']);

function safeHost(url: string): string | null {
  try {
    const parsed = new URL(url);
    if (!SAFE_PROTOCOLS.has(parsed.protocol)) return null;
    return parsed.host;
  } catch {
    return null;
  }
}

export interface AppSourceLinkProps {
  source: SourceRef;
}

export function AppSourceLink({ source }: AppSourceLinkProps) {
  const { t } = useTranslation();
  const host = safeHost(source.url);

  if (host === null) {
    return (
      <Typography
        variant="caption"
        color="warning.main"
        sx={{ display: 'inline-flex', alignItems: 'center', gap: 0.5 }}
      >
        <WarningIcon size={14} />
        {t('flags:card.noSource')}
      </Typography>
    );
  }

  return (
    <MuiLink
      href={source.url}
      target="_blank"
      rel="noopener noreferrer"
      variant="caption"
      sx={{ display: 'inline-flex', alignItems: 'center', gap: 0.5, maxWidth: '100%' }}
    >
      <ArrowSquareOutIcon size={14} />
      <span>{source.label}</span>
      <Typography component="span" variant="caption" color="text.secondary" sx={{ wordBreak: 'break-all' }}>
        ({host})
      </Typography>
    </MuiLink>
  );
}
