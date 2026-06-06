import { Box, Button, IconButton, Paper, Stack, Typography } from '@mui/material';
import { DownloadSimpleIcon, ExportIcon, XIcon } from '@phosphor-icons/react';
import { useCallback, useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { logger } from '@/lib/logger';

/** The browser's `beforeinstallprompt` event — not in the standard DOM lib types, so declared here
 *  (the narrowest boundary) rather than reaching for `as any`. */
interface BeforeInstallPromptEvent extends Event {
  readonly platforms: string[];
  prompt: () => Promise<void>;
  readonly userChoice: Promise<{ outcome: 'accepted' | 'dismissed'; platform: string }>;
}

const DISMISS_KEY = 'pwa-install-dismissed';

/** Already running as an installed standalone app? Then there's nothing to offer. */
function isStandalone(): boolean {
  if (window.matchMedia('(display-mode: standalone)').matches) return true;
  const nav = navigator as Navigator & { standalone?: boolean };
  return nav.standalone === true; // iOS Safari's non-standard flag
}

/** iOS (incl. iPadOS 13+ which reports as Mac) — no `beforeinstallprompt`, so we show a manual hint. */
function isIos(): boolean {
  if (/iphone|ipad|ipod/i.test(navigator.userAgent)) return true;
  return navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1;
}

/** A dismissable „install this app" banner. On Chromium (Android/desktop) it captures the native
 *  `beforeinstallprompt` and offers a one-tap install; on iOS — which has no such event — it shows
 *  a Share ▸ „Add to Home Screen" hint instead. Renders nothing when already installed, dismissed,
 *  or when there's nothing to offer. Themed + i18n, mounted once in AppLayout. */
export function AppInstallPrompt() {
  const { t } = useTranslation();
  const [promptEvent, setPromptEvent] = useState<BeforeInstallPromptEvent | null>(null);
  const [showIosHint, setShowIosHint] = useState(false);

  useEffect(() => {
    if (localStorage.getItem(DISMISS_KEY) === '1') return;
    if (isStandalone()) return;

    // iOS never fires beforeinstallprompt — offer the manual hint and skip the listener wiring.
    if (isIos()) {
      setShowIosHint(true);
      return;
    }

    function onBeforeInstallPrompt(event: Event): void {
      event.preventDefault(); // suppress Chrome's default mini-infobar; we show our own banner
      setPromptEvent(event as BeforeInstallPromptEvent);
    }
    function onInstalled(): void {
      localStorage.setItem(DISMISS_KEY, '1');
      setPromptEvent(null);
    }

    window.addEventListener('beforeinstallprompt', onBeforeInstallPrompt);
    window.addEventListener('appinstalled', onInstalled);
    return () => {
      window.removeEventListener('beforeinstallprompt', onBeforeInstallPrompt);
      window.removeEventListener('appinstalled', onInstalled);
    };
  }, []);

  const dismiss = useCallback(() => {
    localStorage.setItem(DISMISS_KEY, '1');
    setPromptEvent(null);
    setShowIosHint(false);
  }, []);

  const install = useCallback(() => {
    if (promptEvent === null) return;
    void promptEvent.prompt();
    void promptEvent.userChoice
      .then((choice) => {
        logger.info('pwa_install_choice', { outcome: choice.outcome });
        localStorage.setItem(DISMISS_KEY, '1'); // don't re-prompt either way
        setPromptEvent(null);
      })
      .catch((error: unknown) => {
        logger.error('pwa_install_failed', {
          message: error instanceof Error ? error.message : String(error),
        });
      });
  }, [promptEvent]);

  if (promptEvent === null && !showIosHint) return null;

  const canInstall = promptEvent !== null;

  return (
    <Box
      sx={{
        position: 'fixed',
        insetInline: 0,
        bottom: 0,
        zIndex: (theme) => theme.zIndex.snackbar,
        p: 2,
        display: 'flex',
        justifyContent: 'center',
        pointerEvents: 'none',
      }}
    >
      <Paper
        elevation={8}
        sx={{ pointerEvents: 'auto', width: 'min(100%, 520px)', p: 2, border: 1, borderColor: 'divider' }}
      >
        <Stack direction="row" spacing={1.5} alignItems="center">
          <Stack spacing={0.25} sx={{ flex: 1, minWidth: 0 }}>
            <Typography sx={{ fontWeight: 700 }}>{t('pwa.installTitle')}</Typography>
            <Typography variant="body2" color="text.secondary">
              {canInstall ? t('pwa.installBody') : t('pwa.iosHint')}
            </Typography>
          </Stack>
          {canInstall ? (
            <Button variant="contained" startIcon={<DownloadSimpleIcon weight="bold" />} onClick={install}>
              {t('pwa.install')}
            </Button>
          ) : (
            // The Share glyph mirrors the iOS toolbar icon the hint text points at.
            <ExportIcon size={24} weight="bold" aria-hidden />
          )}
          <IconButton aria-label={t('actions.close')} onClick={dismiss} size="small">
            <XIcon />
          </IconButton>
        </Stack>
      </Paper>
    </Box>
  );
}
