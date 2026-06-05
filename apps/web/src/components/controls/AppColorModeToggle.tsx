import { MoonIcon, SunIcon } from '@phosphor-icons/react';
import { useTranslation } from 'react-i18next';
import { useColorMode } from '@/hooks/useColorMode';
import { AppIconButton } from './AppIconButton';

/** Dark/light switch. Shows the icon — and announces the label — of the mode you'd switch INTO. */
export function AppColorModeToggle() {
  const { mode, toggle } = useColorMode();
  const { t } = useTranslation();
  const label = mode === 'dark' ? t('common:theme.switchToLight') : t('common:theme.switchToDark');

  return (
    <AppIconButton label={label} onClick={toggle} color="inherit">
      {mode === 'dark' ? <SunIcon size={20} /> : <MoonIcon size={20} />}
    </AppIconButton>
  );
}
