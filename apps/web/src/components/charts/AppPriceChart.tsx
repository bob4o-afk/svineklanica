import { LineChart } from '@mui/x-charts/LineChart';
import { formatDate } from '@/lib/date';
import { formatMoney, formatMoneyShort } from '@/lib/money';
import { palette } from '@/theme/tokens';
import type { Currency, PriceSeries } from '@/types/api';

export interface AppPriceChartProps {
  series: PriceSeries;
  height?: number;
}

/** Price-over-time line: watch a unit price creep across procurement captures.
 *  Categorical x-axis of formatted capture dates; alarm-red line + soft area fill. */
export function AppPriceChart({ series, height = 340 }: AppPriceChartProps) {
  // ISO strings sort chronologically — order oldest → newest, left → right.
  const points = [...series.points].sort((a, b) => a.captured_at.localeCompare(b.captured_at));
  const labels = points.map((p) => formatDate(p.captured_at));
  const amounts = points.map((p) => p.price.amount);
  const currency: Currency = points[0]?.price.currency ?? 'BGN';

  return (
    <LineChart
      height={height}
      xAxis={[{ data: labels, scaleType: 'point' }]}
      yAxis={[
        { valueFormatter: (value) => formatMoneyShort({ amount: Number(value), currency, vat_included: true }) },
      ]}
      series={[
        {
          data: amounts,
          label: series.product_label,
          color: palette.alarm,
          area: true,
          showMark: true,
          valueFormatter: (value) =>
            value === null ? '' : formatMoney({ amount: value, currency, vat_included: true }),
        },
      ]}
      margin={{ left: 76, right: 16, top: 16, bottom: 28 }}
      slotProps={{ legend: { hidden: true } }}
    />
  );
}
