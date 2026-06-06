import {
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  IconButton,
  Stack,
} from '@mui/material';
import { XIcon } from '@phosphor-icons/react';
import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

export interface AppDialogProps {
  open: boolean;
  title: string;
  onClose: () => void;
  children: ReactNode;
  /** Footer buttons (submit / cancel). */
  actions?: ReactNode;
}

/** The one modal wrapper (source create/edit, confirms). Owns the title bar + close affordance;
 *  the caller supplies body + actions. Closes on backdrop/escape via MUI. */
export function AppDialog({ open, title, onClose, children, actions }: AppDialogProps) {
  const { t } = useTranslation();
  return (
    <Dialog open={open} onClose={onClose} fullWidth maxWidth="sm">
      <DialogTitle component="div">
        <Stack direction="row" alignItems="center" justifyContent="space-between" spacing={1}>
          <span>{title}</span>
          <IconButton size="small" aria-label={t('common:actions.close')} onClick={onClose}>
            <XIcon size={18} />
          </IconButton>
        </Stack>
      </DialogTitle>
      <DialogContent dividers>{children}</DialogContent>
      {actions !== undefined ? <DialogActions>{actions}</DialogActions> : null}
    </Dialog>
  );
}
