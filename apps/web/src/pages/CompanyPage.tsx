import { useParams } from 'react-router-dom';
import { CompanyView } from '@/features/company/CompanyView';
import { useRenderLog } from '@/hooks/useRenderLog';
import { NotFoundPage } from './NotFoundPage';

/** EIK (ЕИК/БУЛСТАТ) is 9–13 digits — clamp it before querying (security.md: validate params). */
const EIK = /^\d{8,13}$/;

export function CompanyPage() {
  useRenderLog('CompanyPage');
  const { eik } = useParams();
  if (eik === undefined || !EIK.test(eik)) return <NotFoundPage />;
  return <CompanyView eik={eik} />;
}
