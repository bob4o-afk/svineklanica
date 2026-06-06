import '@fontsource/inter/400.css';
import '@fontsource/inter/500.css';
import '@fontsource/inter/700.css';
import '@fontsource/jetbrains-mono/400.css';
import '@fontsource/jetbrains-mono/700.css';
import { GlobalStyles as MuiGlobalStyles } from '@mui/material';

/** Self-hosted fonts (Cyrillic included via unicode-range) + base global rules. */
export function GlobalStyles() {
  return (
    <MuiGlobalStyles
      styles={(theme) => ({
        'html, body, #root': { height: '100%' },
        body: { margin: 0 },
        '*:focus-visible': {
          outline: `2px solid ${theme.palette.primary.main}`,
          outlineOffset: 2,
        },
        '::selection': {
          background: theme.palette.primary.main,
          color: theme.palette.primary.contrastText,
        },
      })}
    />
  );
}
