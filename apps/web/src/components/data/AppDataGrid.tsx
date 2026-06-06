import {
  DataGrid,
  type GridColDef,
  type GridRowParams,
  type GridValidRowModel,
} from '@mui/x-data-grid';
import { bgBG } from '@mui/x-data-grid/locales';

const localeText = bgBG.components.MuiDataGrid.defaultProps.localeText;

export interface AppDataGridProps<R extends GridValidRowModel> {
  rows: R[];
  columns: GridColDef<R>[];
  getRowId: (row: R) => string;
  /** Row click handler (receives the row). When set, rows show a pointer cursor. */
  onRowClick?: (row: R) => void;
  loading?: boolean;
  ariaLabel?: string;
}

/** The one DataGrid wrapper (MUI X Community — we use no Premium features, so no license key /
 *  watermark; frontend.md §1). Bulgarian locale, compact + autoHeight, no selection/column-menu
 *  noise. Cell formatting (money/date/empty) lives in the column defs so blanks render as
 *  EMPTY_CELL. Colors come from the MUI theme — none hardcoded. */
export function AppDataGrid<R extends GridValidRowModel>({
  rows,
  columns,
  getRowId,
  onRowClick,
  loading = false,
  ariaLabel,
}: AppDataGridProps<R>) {
  return (
    <DataGrid
      rows={rows}
      columns={columns}
      getRowId={getRowId}
      loading={loading}
      localeText={localeText}
      density="compact"
      autoHeight
      disableRowSelectionOnClick
      disableColumnMenu
      hideFooterSelectedRowCount
      pageSizeOptions={[10, 25, 50]}
      initialState={{ pagination: { paginationModel: { pageSize: 10 } } }}
      sx={{
        border: 1,
        borderColor: 'divider',
        '& .MuiDataGrid-row': onRowClick !== undefined ? { cursor: 'pointer' } : {},
      }}
      {...(onRowClick !== undefined
        ? { onRowClick: (params: GridRowParams<R>) => onRowClick(params.row) }
        : {})}
      {...(ariaLabel !== undefined ? { 'aria-label': ariaLabel } : {})}
    />
  );
}
