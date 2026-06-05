import { useTranslation } from 'react-i18next';
import { AppSeo } from '@/components/layout/AppSeo';
import { AboutView } from '@/features/about/AboutView';
import { useRenderLog } from '@/hooks/useRenderLog';

export function AboutPage() {
  useRenderLog('AboutPage');
  const { t } = useTranslation();
  return (
    <>
      <AppSeo title={t('about:title')} description={t('about:intro')} />
      <AboutView />
    </>
  );
}
