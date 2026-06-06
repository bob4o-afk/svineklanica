import { ClockIcon } from '@phosphor-icons/react';
import { useTranslation } from 'react-i18next';
import { AppButton } from '@/components/controls/AppButton';
import { AppEmptyState } from '@/components/feedback/AppEmptyState';
import { AppSeo } from '@/components/layout/AppSeo';
import { paths } from '@/routes/paths';

export interface ComingSoonViewProps {
  /** Optional override title key; defaults to the generic "coming soon". */
  titleKey?: string;
  /** Keep this placeholder out of search indexes (admin uses this). */
  noindex?: boolean;
}

/** Shared placeholder for routes whose feature lands in a later phase (entity pages, viz, admin).
 *  Owns the page's title (h1 + document title) and SEO so each stub route is a complete page —
 *  one implementation reused everywhere, no per-stub copy-paste. */
export function ComingSoonView({ titleKey, noindex = false }: ComingSoonViewProps) {
  const { t } = useTranslation();
  const title = t(titleKey ?? 'common:soon.title');

  return (
    <>
      <AppSeo title={title} noindex={noindex} />
      <AppEmptyState
        icon={ClockIcon}
        title={title}
        titleComponent="h1"
        description={t('common:soon.body')}
        action={
          <AppButton variant="text" to={paths.home}>
            {t('common:notFound.home')}
          </AppButton>
        }
      />
    </>
  );
}
