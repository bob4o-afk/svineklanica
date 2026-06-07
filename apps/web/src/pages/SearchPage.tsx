import { useTranslation } from 'react-i18next';
import { AppSeo } from '@/components/layout/AppSeo';
import { SearchView } from '@/features/search/SearchView';
import { useRenderLog } from '@/hooks/useRenderLog';

export function SearchPage() {
  useRenderLog('SearchPage');
  const { t } = useTranslation();
  return (
    <>
      <AppSeo
        title={t('search:title')}
        description={t('search:placeholder')}
        keywords={['търсене обществени поръчки', 'търси възложител', 'търси фирма', 'проверка на фирма']}
      />
      <SearchView />
    </>
  );
}
