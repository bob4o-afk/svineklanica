import { useTranslation } from 'react-i18next';
import { AppSeo } from '@/components/layout/AppSeo';
import { HomeView } from '@/features/home/HomeView';
import { useRenderLog } from '@/hooks/useRenderLog';

export function HomePage() {
  useRenderLog('HomePage');
  const { t } = useTranslation();
  return (
    <>
      <AppSeo
        description={t('common:seo.description')}
        keywords={[
          'Парите са обществени Прозрачността не е',
          'обществени поръчки България',
          'съмнителни обществени поръчки',
          'обществени плащания',
          'СЕБРА',
          'ЦАИС ЕОП',
          'свинекланица',
        ]}
      />
      <HomeView />
    </>
  );
}
