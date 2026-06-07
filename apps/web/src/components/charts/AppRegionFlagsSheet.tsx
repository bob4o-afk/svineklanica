import { Box, Drawer, List, ListItemButton, Stack, Typography } from '@mui/material';
import { useTranslation } from 'react-i18next';
import { AppButton } from '@/components/controls/AppButton';
import { flagTypeMeta } from '@/lib/flags';
import { type FlagMapPoint, SEVERITY_COLOR } from '@/lib/mapPoints';
import { regionName } from '@/lib/regions';
import { fonts } from '@/theme/typography';
import { palette } from '@/theme/tokens';

export interface AppRegionFlagsSheetProps {
  /** The region whose flags to show; null = closed. */
  regionCode: string | null;
  onClose: () => void;
  /** All map flag points; the sheet filters to this region. */
  flags: FlagMapPoint[];
  /** Tap a flag → open its post. */
  onSelectFlag: (publicId: string) => void;
  /** "See all in the feed" → the region's full feed. */
  onViewFeed: (regionCode: string) => void;
}

/** Mobile bottom-sheet listing every flag pinned in a region — the touch alternative to the
 *  desktop hover tooltip. Tapping a region on the map opens this instead of redirecting, so a
 *  phone user can see WHAT is flagged there before drilling in (CLAUDE.md §1.2). */
export function AppRegionFlagsSheet({
  regionCode,
  onClose,
  flags,
  onSelectFlag,
  onViewFeed,
}: AppRegionFlagsSheetProps) {
  const { t } = useTranslation();
  const open = regionCode !== null;
  const regionFlags = open ? flags.filter((f) => f.region_code === regionCode) : [];

  return (
    <Drawer
      anchor="bottom"
      open={open}
      onClose={onClose}
      slotProps={{
        paper: {
          sx: {
            borderTopLeftRadius: 8,
            borderTopRightRadius: 8,
            borderTop: `2px solid ${palette.alarm}`,
            maxHeight: '72vh',
          },
        },
      }}
    >
      <Box sx={{ p: 2 }}>
        {/* grab handle */}
        <Box sx={{ width: 36, height: 4, bgcolor: 'divider', borderRadius: 2, mx: 'auto', mb: 1.5 }} />

        <Typography sx={{ fontFamily: fonts.display, fontWeight: 800, fontSize: '1.15rem' }}>
          {regionCode !== null ? regionName(regionCode) : ''}
        </Typography>
        <Typography
          variant="caption"
          color="text.secondary"
          sx={{ fontFamily: fonts.mono, letterSpacing: '0.06em' }}
        >
          {t('viz:map.flagCount', { count: regionFlags.length })}
        </Typography>

        {regionFlags.length > 0 ? (
          <List sx={{ mt: 1 }}>
            {regionFlags.map((f) => (
              <ListItemButton
                key={f.public_id}
                onClick={() => onSelectFlag(f.public_id)}
                sx={{ alignItems: 'flex-start', gap: 1.5, px: 1, borderRadius: '2px' }}
              >
                <Box
                  sx={{
                    width: 10,
                    height: 10,
                    borderRadius: '50%',
                    bgcolor: SEVERITY_COLOR[f.severity],
                    mt: '6px',
                    flexShrink: 0,
                  }}
                />
                <Stack spacing={0.25}>
                  <Typography
                    sx={{
                      fontFamily: fonts.mono,
                      fontSize: '0.6rem',
                      fontWeight: 700,
                      letterSpacing: '0.1em',
                      textTransform: 'uppercase',
                      color: palette.alarm,
                    }}
                  >
                    {t(flagTypeMeta[f.type].i18nKey)}
                  </Typography>
                  <Typography variant="body2" sx={{ fontFamily: fonts.display, fontWeight: 700, lineHeight: 1.25 }}>
                    {f.title ?? t(flagTypeMeta[f.type].i18nKey)}
                  </Typography>
                </Stack>
              </ListItemButton>
            ))}
          </List>
        ) : (
          <Typography variant="body2" color="text.secondary" sx={{ mt: 2 }}>
            {t('viz:map.regionEmpty')}
          </Typography>
        )}

        {regionCode !== null ? (
          <AppButton
            variant="outlined"
            fullWidth
            sx={{ mt: 2 }}
            onClick={() => onViewFeed(regionCode)}
          >
            {t('viz:map.viewFeed')}
          </AppButton>
        ) : null}
      </Box>
    </Drawer>
  );
}
