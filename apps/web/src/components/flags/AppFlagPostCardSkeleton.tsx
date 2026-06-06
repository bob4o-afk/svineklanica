import { Box, Card, CardActions, CardContent, Skeleton, Stack } from '@mui/material';
import { palette } from '@/theme/tokens';

/** Loading placeholder shaped like {@link AppFlagPostCard} — same `punk-card` frame, red top
 *  bar, badge row, headline, body lines and footer — so the feed reserves the exact size of a
 *  real сигнал card and there's no layout jump when data lands (frontend.md §7). */
export function AppFlagPostCardSkeleton() {
  return (
    <Card className="punk-card" aria-hidden>
      <Box sx={{ height: 2, bgcolor: palette.alarm, flexShrink: 0 }} />
      <CardContent>
        {/* Badge row — mirrors the severity/type/sector chips. */}
        <Stack direction="row" spacing={1} sx={{ mb: 1.5 }}>
          <Skeleton variant="rounded" width={68} height={24} animation="wave" />
          <Skeleton variant="rounded" width={92} height={24} animation="wave" />
          <Skeleton variant="rounded" width={56} height={24} animation="wave" />
        </Stack>
        {/* Headline (h6) + subject line. */}
        <Skeleton variant="text" width="75%" sx={{ fontSize: '1.25rem' }} animation="wave" />
        <Skeleton variant="text" width="42%" sx={{ fontSize: '0.75rem', mb: 1 }} animation="wave" />
        {/* Explanation body (~2 lines). */}
        <Skeleton variant="text" width="100%" animation="wave" />
        <Skeleton variant="text" width="90%" sx={{ mb: 1.5 }} animation="wave" />
        {/* Evidence list. */}
        <Skeleton variant="rounded" height={40} animation="wave" />
      </CardContent>
      <CardActions
        sx={{ justifyContent: 'space-between', px: 2, py: 1, borderTop: `1px solid ${palette.alarm}22` }}
      >
        <Skeleton variant="text" width={120} animation="wave" />
        <Skeleton variant="text" width={96} animation="wave" />
      </CardActions>
    </Card>
  );
}
