import { useTranslation } from 'react-i18next';
import { AppSeo } from '@/components/layout/AppSeo';
import { FeedView } from '@/features/feed/FeedView';
import { useRenderLog } from '@/hooks/useRenderLog';

export function FeedPage() {
  useRenderLog('FeedPage');
  const { t } = useTranslation();
  return (
    <>
      <AppSeo title={t('feed:title')} description={t('feed:subtitle')} />
      <FeedView />
    </>
  );
}
