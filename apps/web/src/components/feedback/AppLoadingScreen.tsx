import { keyframes } from '@emotion/react';
import { Box } from '@mui/material';
import { BRAND } from '@/config/brand';
import { useColorMode } from '@/hooks/useColorMode';

import blackFullGray from '@/assets/logos/black_full_gray.svg';
import blackFullRed from '@/assets/logos/black_full_red.svg';
import whiteFullGray from '@/assets/logos/white_full_gray.svg';
import whiteFullRed from '@/assets/logos/white_full_red.svg';

// Smooth cross-fade: red logo fades out while gray fades in, then reverses.
// The two images are stacked; we animate opacity on the gray overlay so the
// red base is always visible underneath — the result reads as a "heartbeat blink".
const blinkIn = keyframes`
  0%,  40% { opacity: 0; }
  50%       { opacity: 1; }
  90%, 100% { opacity: 0; }
`;

export interface AppLoadingScreenProps {
  /** Message shown below the logo. */
  message?: string;
}

/** Full-screen loading state — full wordmark pulsing between red and gray. */
export function AppLoadingScreen({ message }: AppLoadingScreenProps) {
  const { mode } = useColorMode();
  const isDark = mode === 'dark';

  const redSrc = isDark ? whiteFullRed : blackFullRed;
  const graySrc = isDark ? whiteFullGray : blackFullGray;

  return (
    <Box
      role="status"
      aria-label={message ?? BRAND.name}
      sx={{
        position: 'fixed',
        inset: 0,
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'center',
        bgcolor: 'background.default',
        zIndex: 9999,
        gap: 3,
      }}
    >
      {/* Stacked logos: red underneath, gray fades over it */}
      <Box sx={{ position: 'relative', height: 80, width: 'auto' }}>
        <Box
          component="img"
          src={redSrc}
          alt=""
          aria-hidden
          sx={{ height: 80, width: 'auto', display: 'block' }}
        />
        <Box
          component="img"
          src={graySrc}
          alt=""
          aria-hidden
          sx={{
            position: 'absolute',
            top: 0,
            left: 0,
            height: 80,
            width: 'auto',
            display: 'block',
            animation: `${blinkIn} 2s ease-in-out infinite`,
          }}
        />
      </Box>

      {message ? (
        <Box
          component="p"
          sx={{
            m: 0,
            fontFamily: 'JetBrains Mono, monospace',
            fontSize: '0.75rem',
            letterSpacing: '0.12em',
            textTransform: 'uppercase',
            color: 'text.secondary',
          }}
        >
          {message}
        </Box>
      ) : null}
    </Box>
  );
}
