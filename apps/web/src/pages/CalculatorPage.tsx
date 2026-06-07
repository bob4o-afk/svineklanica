import { Box } from '@mui/material';
import { AppCorruptionCalculator } from '@/components/calculator/AppCorruptionCalculator';
import { useRenderLog } from '@/hooks/useRenderLog';

/** "How much of your taxes went to corruption" — the citizen calculator (CLAUDE.md).
 *  Thin page: centers the reusable calculator and asks it to render its heading + SEO. */
export function CalculatorPage() {
  useRenderLog('CalculatorPage');
  return (
    <Box sx={{ maxWidth: 720, mx: 'auto', width: '100%', py: 4, px: 2 }}>
      <AppCorruptionCalculator showHeading />
    </Box>
  );
}
