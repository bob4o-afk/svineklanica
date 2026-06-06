import { CssBaseline, ThemeProvider } from '@mui/material';
import { type ReactNode, useCallback, useEffect, useMemo, useState } from 'react';
import { GlobalStyles } from '@/theme/GlobalStyles';
import { type ColorMode, makeTheme } from '@/theme/theme';
import { ColorModeContext, type ColorModeContextValue } from './colorModeContext';

const STORAGE_KEY = 'cf-color-mode';
const LIGHT_QUERY = '(prefers-color-scheme: light)';

function storedMode(): ColorMode | null {
  const value = typeof localStorage !== 'undefined' ? localStorage.getItem(STORAGE_KEY) : null;
  return value === 'light' || value === 'dark' ? value : null;
}

function prefersLight(): boolean {
  return typeof window !== 'undefined' && typeof window.matchMedia === 'function'
    ? window.matchMedia(LIGHT_QUERY).matches
    : false;
}

function initialMode(): ColorMode {
  return storedMode() ?? (prefersLight() ? 'light' : 'dark');
}

export function ColorModeProvider({ children }: { children: ReactNode }) {
  const [mode, setMode] = useState<ColorMode>(initialMode);

  // Follow OS theme changes — but only while the user hasn't made an explicit choice. Once they
  // toggle, their preference is persisted and wins. (We intentionally do NOT write on mount, so a
  // first-time visitor's OS preference isn't frozen into localStorage.)
  useEffect(() => {
    if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') return;
    if (storedMode() !== null) return;
    const mql = window.matchMedia(LIGHT_QUERY);
    const onChange = (event: MediaQueryListEvent): void => setMode(event.matches ? 'light' : 'dark');
    mql.addEventListener('change', onChange);
    return () => mql.removeEventListener('change', onChange);
  }, []);

  const toggle = useCallback(() => {
    setMode((current) => {
      const next = current === 'dark' ? 'light' : 'dark';
      if (typeof localStorage !== 'undefined') localStorage.setItem(STORAGE_KEY, next);
      return next;
    });
  }, []);

  const theme = useMemo(() => makeTheme(mode), [mode]);
  const value = useMemo<ColorModeContextValue>(() => ({ mode, toggle }), [mode, toggle]);

  return (
    <ColorModeContext.Provider value={value}>
      <ThemeProvider theme={theme}>
        <CssBaseline />
        <GlobalStyles />
        {children}
      </ThemeProvider>
    </ColorModeContext.Provider>
  );
}
