import { Box, Divider, Stack, Typography } from '@mui/material';
import { ArrowRightIcon, MagnifyingGlassIcon } from '@phosphor-icons/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { AppBrandLockup } from '@/components/brand/AppBrandLockup';
import { AppButton } from '@/components/controls/AppButton';
import { AppLink } from '@/components/controls/AppLink';
import { AppSearchInput } from '@/components/controls/AppSearchInput';
import { FeedList } from '@/features/feed/FeedList';
import { useStats } from '@/hooks/queries/useStats';
import { EMPTY_CELL, formatNumber } from '@/lib/format';
import { paths } from '@/routes/paths';
import { fonts } from '@/theme/typography';
import { palette } from '@/theme/tokens';

function StatCounter({ value, label }: { value: string; label: string }) {
  return (
    <Box sx={{ borderLeft: `2px solid ${palette.alarm}`, pl: 2 }}>
      <Typography
        sx={{
          fontFamily: fonts.display,
          fontWeight: 800,
          fontSize: { xs: '1.75rem', sm: '2.25rem' },
          lineHeight: 1,
          fontVariantNumeric: 'tabular-nums',
        }}
        data-counter
      >
        {value}
      </Typography>
      <Typography
        sx={{
          fontFamily: fonts.mono,
          fontWeight: 600,
          fontSize: '0.65rem',
          letterSpacing: '0.12em',
          textTransform: 'uppercase',
          color: 'text.secondary',
          mt: 0.5,
        }}
      >
        {label}
      </Typography>
    </Box>
  );
}

export function HomeView() {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const [searchInput, setSearchInput] = useState('');
  const stats = useStats();
  const stat = stats.data;

  return (
    <Stack spacing={0}>
      {/* ── HERO: centered badge + name ────────────────────────────────────── */}
      {/* This is the first screen. Logo and name sit centered; everything else
          becomes visible as the user scrolls. */}
      <Box
        sx={{
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'center',
          textAlign: 'center',
          minHeight: 'calc(100svh - 96px)',
          pt: { xs: 4, sm: 6 },
          pb: { xs: 4, sm: 6 },
          gap: 3,
        }}
      >
        <AppBrandLockup size="hero" nameComponent="h1" />
      </Box>

      {/* ── CONTENT: visible on scroll ─────────────────────────────────────── */}
      <Stack spacing={8} sx={{ pb: 8 }}>
        {/* Headline + subtitle + stats + CTAs */}
        <Stack spacing={4}>
          <Box>
            <Typography
              component="h2"
              sx={{
                fontFamily: fonts.display,
                fontWeight: 800,
                fontSize: { xs: '2rem', sm: '2.75rem', md: '3.5rem' },
                lineHeight: 1.05,
                letterSpacing: '-0.03em',
              }}
            >
              {t('home:hero.titleLine1')}
            </Typography>
            <Typography
              component="span"
              sx={{
                display: 'block',
                fontFamily: fonts.display,
                fontWeight: 800,
                fontSize: { xs: '2rem', sm: '2.75rem', md: '3.5rem' },
                lineHeight: 1.05,
                letterSpacing: '-0.03em',
                color: palette.alarm,
              }}
            >
              {t('home:hero.titleLine2')}
            </Typography>
          </Box>

          <Typography
            variant="body1"
            color="text.secondary"
            sx={{ maxWidth: 600, lineHeight: 1.7 }}
          >
            {t('home:hero.subtitle')}
          </Typography>

          <Stack direction="row" spacing={4} flexWrap="wrap" useFlexGap>
            <StatCounter value={stat ? formatNumber(stat.tenders) : EMPTY_CELL} label={t('home:stats.tenders')} />
            <StatCounter value={stat ? formatNumber(stat.flags) : EMPTY_CELL} label={t('home:stats.flags')} />
            <StatCounter value={stat ? formatNumber(stat.detectors) : EMPTY_CELL} label={t('home:stats.detectors')} />
          </Stack>

          <Stack direction="row" spacing={2} flexWrap="wrap" useFlexGap>
            <AppButton to={paths.feed} variant="contained" endIcon={<ArrowRightIcon />}>
              {t('home:hero.cta')}
            </AppButton>
            <AppButton to={paths.search} variant="outlined" startIcon={<MagnifyingGlassIcon />}>
              {t('home:hero.ctaSearch')}
            </AppButton>
          </Stack>
        </Stack>

        {/* Search bar */}
        <AppSearchInput
          value={searchInput}
          onChange={setSearchInput}
          onSubmit={(q) => void navigate(`${paths.search}?q=${encodeURIComponent(q)}`)}
        />

        <Divider />

        {/* Latest flags */}
        <Stack spacing={3}>
          <Stack direction="row" justifyContent="space-between" alignItems="baseline">
            <Typography variant="h5" component="h3" sx={{ fontFamily: fonts.display, fontWeight: 800 }}>
              {t('home:latest.title')}
            </Typography>
            <AppLink
              to={paths.feed}
              sx={{ fontFamily: fonts.mono, fontSize: '0.75rem', letterSpacing: '0.08em', textTransform: 'uppercase' }}
            >
              {t('home:latest.all')}
            </AppLink>
          </Stack>
          <FeedList query={{ sort: 'newest' }} limit={3} />
        </Stack>
      </Stack>
    </Stack>
  );
}
