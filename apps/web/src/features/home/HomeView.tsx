import { Box, Divider, Stack, Typography } from '@mui/material';
import { ArrowRightIcon, MagnifyingGlassIcon } from '@phosphor-icons/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { AppButton } from '@/components/controls/AppButton';
import { AppLink } from '@/components/controls/AppLink';
import { AppSearchInput } from '@/components/controls/AppSearchInput';
import { AppReveal } from '@/components/motion/AppReveal';
import { FeedList } from '@/features/feed/FeedList';
import { useStats } from '@/hooks/queries/useStats';
import { EMPTY_CELL, formatNumber } from '@/lib/format';
import { paths } from '@/routes/paths';
import { fonts } from '@/theme/typography';
import { palette } from '@/theme/tokens';
import { HomeHero } from './HomeHero';

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
    <Stack spacing={0} sx={{ position: 'relative' }}>
      {/* ── HERO: pinned splash (self-contained scroll animation) ──────────── */}
      <HomeHero />

      {/* ── CONTENT: slides up over the hero on scroll ─────────────────────── */}
      <Stack
        spacing={8}
        sx={{
          position: 'relative',
          zIndex: 1,
          bgcolor: 'background.default',
          pb: 8,
        }}
      >
        {/* Headline + subtitle + stats + CTAs */}
        <Stack spacing={4}>
          <AppReveal>
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
          </AppReveal>

          <AppReveal delay={80}>
            <Typography
              variant="body1"
              color="text.secondary"
              sx={{ maxWidth: 600, lineHeight: 1.7 }}
            >
              {t('home:hero.subtitle')}
            </Typography>
          </AppReveal>

          <Stack direction="row" spacing={4} flexWrap="wrap" useFlexGap>
            <AppReveal delay={0}>
              <StatCounter value={stat ? formatNumber(stat.tenders) : EMPTY_CELL} label={t('home:stats.tenders')} />
            </AppReveal>
            <AppReveal delay={120}>
              <StatCounter value={stat ? formatNumber(stat.flags) : EMPTY_CELL} label={t('home:stats.flags')} />
            </AppReveal>
            <AppReveal delay={240}>
              <StatCounter value={stat ? formatNumber(stat.detectors) : EMPTY_CELL} label={t('home:stats.detectors')} />
            </AppReveal>
          </Stack>

          <AppReveal delay={120}>
            <Stack direction="row" spacing={2} flexWrap="wrap" useFlexGap>
              <AppButton to={paths.feed} variant="contained" endIcon={<ArrowRightIcon />}>
                {t('home:hero.cta')}
              </AppButton>
              <AppButton to={paths.search} variant="outlined" startIcon={<MagnifyingGlassIcon />}>
                {t('home:hero.ctaSearch')}
              </AppButton>
            </Stack>
          </AppReveal>
        </Stack>

        {/* Search bar */}
        <AppReveal>
          <AppSearchInput
            value={searchInput}
            onChange={setSearchInput}
            onSubmit={(q) => void navigate(`${paths.search}?q=${encodeURIComponent(q)}`)}
          />
        </AppReveal>

        <Divider />

        {/* Latest flags */}
        <Stack spacing={3}>
          <AppReveal>
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
          </AppReveal>
          <FeedList query={{ sort: 'newest' }} limit={3} />
        </Stack>
      </Stack>
    </Stack>
  );
}
