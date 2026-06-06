import { useParams } from 'react-router-dom';
import { AuthorityView } from '@/features/authority/AuthorityView';
import { useRenderLog } from '@/hooks/useRenderLog';
import { NotFoundPage } from './NotFoundPage';

/** Public_id is an opaque slug/UUID — clamp it before querying (security.md: validate params). */
const PUBLIC_ID = /^[A-Za-z0-9-]{1,64}$/;

export function AuthorityPage() {
  useRenderLog('AuthorityPage');
  const { publicId } = useParams();
  if (publicId === undefined || !PUBLIC_ID.test(publicId)) return <NotFoundPage />;
  return <AuthorityView publicId={publicId} />;
}
