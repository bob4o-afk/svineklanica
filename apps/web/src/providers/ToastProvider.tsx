import { Alert, Snackbar } from '@mui/material';
import { type ReactNode, useCallback, useMemo, useState } from 'react';
import { ToastContext, type ToastContextValue, type ToastSeverity } from './toastContext';

export function ToastProvider({ children }: { children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const [message, setMessage] = useState('');
  const [severity, setSeverity] = useState<ToastSeverity>('info');

  const showToast = useCallback((nextMessage: string, nextSeverity: ToastSeverity = 'info') => {
    setMessage(nextMessage);
    setSeverity(nextSeverity);
    setOpen(true);
  }, []);

  const value = useMemo<ToastContextValue>(() => ({ showToast }), [showToast]);

  return (
    <ToastContext.Provider value={value}>
      {children}
      <Snackbar
        open={open}
        autoHideDuration={4000}
        onClose={() => setOpen(false)}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'center' }}
      >
        <Alert severity={severity} variant="filled" onClose={() => setOpen(false)}>
          {message}
        </Alert>
      </Snackbar>
    </ToastContext.Provider>
  );
}
