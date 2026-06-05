import { createTheme, type Theme } from '@mui/material/styles';
import { components } from './components';
import { typography } from './typography';
import { darkColors, lightColors, palette, radii } from './tokens';

export type ColorMode = 'light' | 'dark';

export function makeTheme(mode: ColorMode): Theme {
  const c = mode === 'dark' ? darkColors : lightColors;
  return createTheme({
    palette: {
      mode,
      primary: { main: c.accent, contrastText: c.onAccent },
      error: { main: palette.alarm, contrastText: palette.bone },
      warning: { main: palette.rust, contrastText: palette.bone },
      background: { default: c.bg, paper: c.paper },
      text: { primary: c.text, secondary: palette.muted },
      divider: c.divider,
    },
    shape: { borderRadius: radii.md },
    typography,
    components,
  });
}
