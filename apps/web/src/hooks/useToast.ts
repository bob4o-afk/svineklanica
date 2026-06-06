import { useContext } from 'react';
import { ToastContext, type ToastContextValue } from '@/providers/toastContext';

export function useToast(): ToastContextValue {
  const ctx = useContext(ToastContext);
  if (ctx === null) throw new Error('useToast must be used within ToastProvider');
  return ctx;
}
