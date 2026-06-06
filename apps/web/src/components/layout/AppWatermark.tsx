import { Box } from '@mui/material';
import { fonts } from '@/theme/typography';

const LINE = 'ВИЖДАМЕ · ЗНАЕМ · ПОМНИМ · ';
// Repeat enough to overflow a 200vw-wide rotated container
const ROW = LINE.repeat(10);
const ROW_COUNT = 22;

/** Fixed full-screen watermark. A single 200×200% container is rotated -8° so it covers
 *  all four corners; rows of text fill it with even spacing; alternate rows are shifted
 *  left by half a repeat unit so the pattern never lines up in visible columns. */
export function AppWatermark() {
  return (
    <Box
      aria-hidden
      sx={{
        position: 'fixed',
        top: '-50%',
        left: '-50%',
        width: '200%',
        height: '200%',
        pointerEvents: 'none',
        zIndex: 0,
        userSelect: 'none',
        transform: 'rotate(-8deg)',
        display: 'flex',
        flexDirection: 'column',
        justifyContent: 'space-around',
      }}
    >
      {Array.from({ length: ROW_COUNT }, (_, i) => (
        <Box
          key={i}
          sx={{
            fontFamily: fonts.display,
            fontWeight: 800,
            fontSize: '14px',
            letterSpacing: '0.26em',
            textTransform: 'uppercase',
            color: 'text.primary',
            opacity: 0.04,
            whiteSpace: 'nowrap',
            lineHeight: 1,
            // Stagger alternate rows so columns never align
            marginLeft: i % 2 === 0 ? 0 : '-18ch',
          }}
        >
          {ROW}
        </Box>
      ))}
    </Box>
  );
}
