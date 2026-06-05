import { Stack, Typography } from '@mui/material';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useSearchParams } from 'react-router-dom';
import { AppSearchInput } from '@/components/controls/AppSearchInput';
import { AppEmptyState } from '@/components/feedback/AppEmptyState';
import { AppErrorState } from '@/components/feedback/AppErrorState';
import { AppSkeleton } from '@/components/feedback/AppSkeleton';
import { AppLink } from '@/components/controls/AppLink';
import { SEARCH_MIN_LENGTH, useSearch } from '@/hooks/queries/useSearch';
import { formatEik } from '@/lib/format';
import { paths } from '@/routes/paths';

/** Hard cap on the query length we send (security.md: clamp user input at the boundary). */
const MAX_QUERY = 100;

/** Global search: an input bound to `?q=` (debounced, shareable), then grouped results
 *  (authorities / companies / tenders) each linking to its destination. */
export function SearchView() {
  const { t } = useTranslation();
  const [params, setParams] = useSearchParams();
  const [input, setInput] = useState(params.get('q') ?? '');
  const query = input.trim().slice(0, MAX_QUERY);

  // Debounce the (capped) query into the URL so a search is shareable without spamming history.
  useEffect(() => {
    const id = setTimeout(() => {
      setParams(
        (prev) => {
          const copy = new URLSearchParams(prev);
          if (query.length > 0) copy.set('q', query);
          else copy.delete('q');
          return copy;
        },
        { replace: true },
      );
    }, 300);
    return () => clearTimeout(id);
  }, [query, setParams]);

  const results = useSearch(query);
  const data = results.data;
  const total =
    data !== undefined
      ? data.authorities.length + data.companies.length + data.tenders.length
      : 0;

  return (
    <Stack spacing={3}>
      <Stack spacing={0.5}>
        <Typography variant="h4" component="h1">
          {t('search:title')}
        </Typography>
      </Stack>

      <AppSearchInput value={input} onChange={setInput} autoFocus />

      {query.length < SEARCH_MIN_LENGTH ? (
        <AppEmptyState title={t('search:title')} description={t('search:prompt')} />
      ) : results.isPending ? (
        <AppSkeleton count={3} />
      ) : results.isError ? (
        <AppErrorState
          title={t('search:empty.title')}
          message={t('search:empty.body')}
          error={results.error}
          onRetry={() => void results.refetch()}
        />
      ) : total === 0 ? (
        <AppEmptyState title={t('search:empty.title')} description={t('search:empty.body')} />
      ) : (
        <Stack spacing={3}>
          {data !== undefined && data.authorities.length > 0 ? (
            <section>
              <Typography variant="overline" color="text.secondary">
                {t('search:groups.authorities')}
              </Typography>
              <Stack spacing={0.5} sx={{ mt: 0.5 }}>
                {data.authorities.map((a) => (
                  <AppLink key={a.public_id} to={paths.authority(a.public_id)}>
                    {a.name}
                  </AppLink>
                ))}
              </Stack>
            </section>
          ) : null}

          {data !== undefined && data.companies.length > 0 ? (
            <section>
              <Typography variant="overline" color="text.secondary">
                {t('search:groups.companies')}
              </Typography>
              <Stack spacing={0.5} sx={{ mt: 0.5 }}>
                {data.companies.map((c) => (
                  <AppLink key={c.public_id} to={paths.company(c.eik)}>
                    {c.name} · {t('entity:eik')} {formatEik(c.eik)}
                  </AppLink>
                ))}
              </Stack>
            </section>
          ) : null}

          {data !== undefined && data.tenders.length > 0 ? (
            <section>
              <Typography variant="overline" color="text.secondary">
                {t('search:groups.tenders')}
              </Typography>
              <Stack spacing={0.5} sx={{ mt: 0.5 }}>
                {data.tenders.map((tender) => (
                  <AppLink
                    key={tender.public_id}
                    to={`${paths.feed}?q=${encodeURIComponent(tender.title)}`}
                  >
                    {tender.title}
                  </AppLink>
                ))}
              </Stack>
            </section>
          ) : null}
        </Stack>
      )}
    </Stack>
  );
}
