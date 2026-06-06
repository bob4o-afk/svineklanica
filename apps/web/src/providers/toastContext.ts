import { createContext } from 'react';

export type ToastSeverity = 'success' | 'error' | 'info';

export interface ToastContextValue {
  showToast: (message: string, severity?: ToastSeverity) => void;
}

export const ToastContext = createContext<ToastContextValue | null>(null);
