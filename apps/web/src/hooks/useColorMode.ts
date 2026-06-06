import { useContext } from 'react';
import { ColorModeContext, type ColorModeContextValue } from '@/providers/colorModeContext';

export function useColorMode(): ColorModeContextValue {
  const ctx = useContext(ColorModeContext);
  if (ctx === null) throw new Error('useColorMode must be used within ColorModeProvider');
  return ctx;
}
