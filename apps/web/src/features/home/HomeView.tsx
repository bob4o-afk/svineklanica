import { Box, Divider, Stack, Typography } from '@mui/material';
import { ArrowRightIcon, MagnifyingGlassIcon } from '@phosphor-icons/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { AppButton } from '@/components/controls/AppButton';
import { AppLink } from '@/components/controls/AppLink';
import { AppSearchInput } from '@/components/controls/AppSearchInput';
import { FeedList } from '@/features/feed/FeedList';
import { useColorMode } from '@/hooks/useColorMode';
import { paths } from '@/routes/paths';
import { fonts } from '@/theme/typography';
import { palette } from '@/theme/tokens';
import { BRAND } from '@/config/brand';

import blackFullRed from '@/assets/logos/black_full_red.svg';
import whiteFullRed from '@/assets/logos/white_full_red.svg';

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
  const { mode } = useColorMode();
  const isDark = mode === 'dark';
  const fullLogoSrc = isDark ? whiteFullRed : blackFullRed;
  const [first, second] = BRAND.nameParts;

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
        <Box
          component="img"
          src={fullLogoSrc}
          alt={BRAND.name}
          sx={{
            width: { xs: 180, sm: 240, md: 300 },
            height: 'auto',
            display: 'block',
          }}
        />

        <Typography
          component="h1"
          sx={{
            fontFamily: fonts.display,
            fontWeight: 800,
            fontSize: { xs: '2.75rem', sm: '4rem', md: '5.5rem' },
            lineHeight: 1,
            letterSpacing: '-0.03em',
            textTransform: 'uppercase',
            userSelect: 'none',
          }}
        >
          {first}
          <Box component="span" sx={{ color: palette.alarm }}>
            {second}
          </Box>
        </Typography>

        <Typography
          sx={{
            fontFamily: fonts.mono,
            fontWeight: 600,
            fontSize: { xs: '0.7rem', sm: '0.8rem' },
            letterSpacing: '0.16em',
            textTransform: 'uppercase',
            color: 'text.secondary',
          }}
        >
          {BRAND.tagline}
        </Typography>
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
            <StatCounter value="3 249" label={t('home:stats.tenders')} />
            <StatCounter value="847" label={t('home:stats.flags')} />
            <StatCounter value="12" label={t('home:stats.detectors')} />
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
