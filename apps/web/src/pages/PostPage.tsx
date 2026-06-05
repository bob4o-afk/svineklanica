import { useParams } from 'react-router-dom';
import { PostView } from '@/features/post/PostView';
import { useRenderLog } from '@/hooks/useRenderLog';
import { NotFoundPage } from './NotFoundPage';

export function PostPage() {
  useRenderLog('PostPage');
  const { publicId } = useParams();
  if (publicId === undefined || publicId === '') return <NotFoundPage />;
  return <PostView publicId={publicId} />;
}
