import { useParams } from 'react-router-dom';
import { AdminReviewView } from '@/features/admin/AdminReviewView';
import { useRenderLog } from '@/hooks/useRenderLog';
import { NotFoundPage } from '@/pages/NotFoundPage';

export function AdminReviewPage() {
  useRenderLog('AdminReviewPage');
  const { publicId } = useParams();
  if (publicId === undefined || publicId === '') return <NotFoundPage />;
  return <AdminReviewView publicId={publicId} />;
}
