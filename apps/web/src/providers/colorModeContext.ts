import { createContext } from 'react';
import type { ColorMode } from '@/theme/theme';

export interface ColorModeContextValue {
  mode: ColorMode;
  toggle: () => void;
}

export const ColorModeContext = createContext<ColorModeContextValue | null>(null);
