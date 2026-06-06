import { useTheme } from '@mui/material/styles';
import { ChartsReferenceLine } from '@mui/x-charts/ChartsReferenceLine';
import { LineChart } from '@mui/x-charts/LineChart';
import { useTranslation } from 'react-i18next';
import { formatDate } from '@/lib/date';
import { formatMoney, formatMoneyAxis } from '@/lib/money';
import { findPriceOutlierIndex } from '@/lib/outlier';
import { fonts } from '@/theme/typography';
import { palette } from '@/theme/tokens';
import type { Currency, PriceSeries } from '@/types/api';

export interface AppPriceChartProps {
  series: PriceSeries;
  height?: number;
}

/** Price-over-time line: watch a unit price creep across procurement captures.
 *  Categorical x-axis of formatted capture dates; alarm-red line + soft area fill. The single
 *  most-overpriced capture (frontend.md §10) is highlighted with a contrasting mark + a labelled
 *  reference line. Themed for light + dark. */
export function AppPriceChart({ series, height = 340 }: AppPriceChartProps) {
  const theme = useTheme();
  const { t } = useTranslation();

  // ISO strings sort chronologically — order oldest → newest, left → right.
  const points = [...series.points].sort((a, b) => a.captured_at.localeCompare(b.captured_at));
  const labels = points.map((p) => formatDate(p.captured_at));
  const amounts = points.map((p) => p.price.amount);
  const currency: Currency = points[0]?.price.currency ?? 'BGN';

  // High-contrast against the alarm-red line in either theme.
  const emphasis = theme.palette.mode === 'dark' ? palette.bone : palette.ink;
  const outlierIndex = findPriceOutlierIndex(amounts);
  const outlierTick = outlierIndex !== null ? labels[outlierIndex] : undefined;
  const outlierData = amounts.map((value, index) => (index === outlierIndex ? value : null));

  return (
    <LineChart
      height={height}
      xAxis={[{ data: labels, scaleType: 'point' }]}
      yAxis={[
        { valueFormatter: (value) => formatMoneyAxis({ amount: Number(value), currency, vat_included: true }) },
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
        ...(outlierIndex !== null
          ? [
              {
                data: outlierData,
                label: t('viz:price.outlierLabel'),
                color: emphasis,
                showMark: true,
                valueFormatter: (value: number | null) =>
                  value === null ? '' : formatMoney({ amount: value, currency, vat_included: true }),
              },
            ]
          : []),
      ]}
      margin={{ left: 76, right: 40, top: 16, bottom: 28 }}
      slotProps={{ legend: { hidden: true } }}
    >
      {outlierTick !== undefined ? (
        <ChartsReferenceLine
          x={outlierTick}
          label={t('viz:price.outlierLabel')}
          labelAlign="start"
          lineStyle={{ stroke: emphasis, strokeDasharray: '5 4', strokeWidth: 1.5 }}
          labelStyle={{ fontFamily: fonts.mono, fontSize: 11, fontWeight: 700, fill: emphasis }}
        />
      ) : null}
    </LineChart>
  );
}
