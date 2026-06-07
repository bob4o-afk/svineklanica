import {
  Box,
  Card,
  CardContent,
  Chip,
  InputAdornment,
  Stack,
  Typography,
} from '@mui/material';
import { type FormEvent, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AppButton } from '@/components/controls/AppButton';
import { AppLink } from '@/components/controls/AppLink';
import { AppTextField } from '@/components/controls/AppTextField';
import { AppEmptyState } from '@/components/feedback/AppEmptyState';
import { AppErrorState } from '@/components/feedback/AppErrorState';
import { AppSkeleton } from '@/components/feedback/AppSkeleton';
import { AppSourceLink } from '@/components/flags/AppSourceLink';
import { AppSeo } from '@/components/layout/AppSeo';
import { AppCountUp } from '@/components/motion/AppCountUp';
import { useCorruptionTax } from '@/hooks/queries/useCorruptionTax';
import { eurToBgn, formatMoney, formatMoneyShort } from '@/lib/money';
import { paths } from '@/routes/paths';
import type { CorruptionTax } from '@/types/api';

const percentFmt = new Intl.NumberFormat('bg-BG', { style: 'percent', maximumFractionDigits: 1 });

/** A typical citizen yearly tax bill in EUR — pre-filled so a result shows on first load. */
const DEFAULT_TAXES = 800;

export interface AppCorruptionCalculatorProps {
  /** Pre-filled tax amount so the calculator computes immediately (cached per amount). */
  defaultTaxes?: number;
  /** Render the page-level heading + intro + <title> (dedicated page only). */
  showHeading?: boolean;
  /** Headline only (big % + the two summary lines + a link to the full breakdown) — for embeds. */
  compact?: boolean;
}

/** The corruption-tax calculator (CLAUDE.md): enter taxes paid → the flagged share of public
 *  spend projected onto that amount, with cases linking to flag-posts. Reusable — embedded on the
 *  home page (big % only) and on its dedicated page (with heading). Results are cached per amount
 *  (frontend.md §9), so a prefilled value computes once and only refetches when the data goes stale. */
export function AppCorruptionCalculator({
  defaultTaxes = DEFAULT_TAXES,
  showHeading = false,
  compact = false,
}: AppCorruptionCalculatorProps) {
  const { t } = useTranslation('calculator');
  // The user types EUR; the API works in лв, so we query with the лв-converted amount
  // (and every displayed amount converts back to € via the money formatters).
  const [input, setInput] = useState(String(defaultTaxes));
  const [taxesBgn, setTaxesBgn] = useState(eurToBgn(defaultTaxes));
  const query = useCorruptionTax(taxesBgn);

  const onSubmit = (event: FormEvent) => {
    event.preventDefault();
    const eur = Number(input.replace(',', '.'));
    setTaxesBgn(Number.isFinite(eur) && eur > 0 ? eurToBgn(eur) : 0);
  };

  return (
    <Stack spacing={4} sx={{ width: '100%' }}>
      {showHeading ? (
        <>
          <AppSeo
            title={t('seoTitle')}
            description={t('seoDescription')}
            keywords={['къде ми отиват парите', 'данъци корупция', 'калкулатор корупция', 'данъци България']}
          />
          <Box>
            <Typography variant="h4" component="h1" sx={{ fontWeight: 800 }}>
              {t('heading')}
            </Typography>
            <Typography color="text.secondary" sx={{ mt: 1 }}>
              {t('intro')}
            </Typography>
          </Box>
        </>
      ) : null}

      <Stack
        component="form"
        direction={{ xs: 'column', sm: 'row' }}
        spacing={2}
        alignItems="flex-start"
        onSubmit={onSubmit}
      >
        <AppTextField
          label={t('inputLabel')}
          helperText={t('inputHelper')}
          value={input}
          onChange={(event) => setInput(event.target.value)}
          type="number"
          inputProps={{ min: 0, inputMode: 'decimal' }}
          InputProps={{
            endAdornment: (
              <InputAdornment position="end">{t('common:units.eurShort')}</InputAdornment>
            ),
          }}
        />
        <AppButton type="submit" sx={{ mt: { sm: '4px' } }}>
          {t('submit')}
        </AppButton>
      </Stack>

      {query.isError ? (
        <AppErrorState message={t('error')} error={query.error} onRetry={() => void query.refetch()} />
      ) : null}

      {taxesBgn > 0 && query.isPending && !query.isError ? <AppSkeleton count={2} /> : null}

      {query.data ? <CalculatorResult data={query.data} compact={compact} /> : null}

      <Typography variant="caption" color="text.secondary">
        {t('disclaimer')}
      </Typography>
    </Stack>
  );
}

function CalculatorResult({ data, compact }: { data: CorruptionTax; compact: boolean }) {
  const { t } = useTranslation('calculator');

  if (data.total_spend.amount <= 0) {
    return <AppEmptyState title={t('empty')} />;
  }

  const percent = percentFmt.format(data.corruption_rate);

  return (
    <Stack spacing={3}>
      <Box sx={{ textAlign: 'center' }}>
        <Typography
          aria-hidden
          sx={{
            fontSize: { xs: '3.5rem', sm: '5rem' },
            fontWeight: 900,
            color: 'error.main',
            lineHeight: 1,
          }}
        >
          <AppCountUp
            value={data.corruption_rate * 100}
            format={(n) => `${n}%`}
          />
        </Typography>
        <Typography variant="h6" component="p">
          {t('resultPercent', { percent })}
        </Typography>
        <Typography color="text.secondary" sx={{ mt: 1 }}>
          {t('resultAmount', {
            taxes: formatMoney(data.taxes_paid),
            amount: formatMoney(data.user_corruption_amount),
          })}
        </Typography>
        <Typography variant="body2" color="text.secondary">
          {t('ofTotal', {
            flagged: formatMoneyShort(data.flagged_spend),
            total: formatMoneyShort(data.total_spend),
          })}
        </Typography>
      </Box>

      {compact ? (
        <Box sx={{ textAlign: 'center' }}>
          <AppLink to={paths.calculator} sx={{ fontWeight: 700 }}>
            {t('seeMore')}
          </AppLink>
        </Box>
      ) : (
        <>
          {data.per_sphere.length > 0 ? (
            <Box>
              <Typography variant="overline" color="text.secondary">
                {t('perSphereHeading')}
              </Typography>
              <Stack spacing={1} sx={{ mt: 1 }}>
                {data.per_sphere.map((sphere, index) => (
                  <Stack
                    key={sphere.sphere_label ?? `unclassified-${index}`}
                    direction="row"
                    justifyContent="space-between"
                    gap={2}
                  >
                    <Typography>{sphere.sphere_label ?? t('unclassified')}</Typography>
                    <Typography color="text.secondary">
                      {percentFmt.format(sphere.rate)} · {formatMoney(sphere.user_amount)}
                    </Typography>
                  </Stack>
                ))}
              </Stack>
            </Box>
          ) : null}

          {data.top_cases.length > 0 ? (
            <Box>
              <Typography variant="overline" color="text.secondary">
                {t('casesHeading')}
              </Typography>
              <Stack spacing={1.5} sx={{ mt: 1 }}>
                {data.top_cases.map((item) => (
                  <Card key={item.flag_public_id ?? `${item.kind}-${item.source_url}`} variant="outlined">
                    <CardContent>
                      <Stack direction="row" justifyContent="space-between" gap={2} flexWrap="wrap">
                        <Box sx={{ minWidth: 0, flex: 1 }}>
                          {item.flag_public_id !== undefined ? (
                            <AppLink to={paths.post(item.flag_public_id)} sx={{ fontWeight: 700 }}>
                              {item.title}
                            </AppLink>
                          ) : (
                            <Typography sx={{ fontWeight: 700 }}>{item.title}</Typography>
                          )}
                          <Stack direction="row" spacing={1} alignItems="center" sx={{ mt: 0.5 }}>
                            <Chip
                              size="small"
                              color="error"
                              variant="outlined"
                              label={t('suspicion', { score: item.score })}
                            />
                            <AppSourceLink
                              source={{ url: item.source_url, label: item.source_url, fetched_at: '' }}
                            />
                          </Stack>
                        </Box>
                        <Box sx={{ textAlign: 'right' }}>
                          <Typography sx={{ fontWeight: 700 }}>{formatMoneyShort(item.amount)}</Typography>
                          <Typography variant="caption" color="error.main" sx={{ display: 'block' }}>
                            {formatMoney(item.user_share)} {t('yourShare')}
                          </Typography>
                        </Box>
                      </Stack>
                    </CardContent>
                  </Card>
                ))}
              </Stack>
            </Box>
          ) : null}
        </>
      )}
    </Stack>
  );
}
