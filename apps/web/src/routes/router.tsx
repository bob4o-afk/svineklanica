import { createBrowserRouter } from 'react-router-dom';
import { AppLayout } from '@/components/layout/AppLayout';
import { AboutPage } from '@/pages/AboutPage';
import { AuthorityPage } from '@/pages/AuthorityPage';
import { CompanyPage } from '@/pages/CompanyPage';
import { FeedPage } from '@/pages/FeedPage';
import { HomePage } from '@/pages/HomePage';
import { MapPage } from '@/pages/MapPage';
import { NetworkPage } from '@/pages/NetworkPage';
import { NotFoundPage } from '@/pages/NotFoundPage';
import { PostPage } from '@/pages/PostPage';
import { PricePage } from '@/pages/PricePage';
import { AdminPlaceholderPage } from '@/pages/admin/AdminPlaceholderPage';
import { ProtectedRoute } from './ProtectedRoute';
import { paths, patterns } from './paths';

/** The whole route tree under one shared AppLayout. Public routes are eager (small); the admin
 *  area sits behind ProtectedRoute and is a placeholder until Phase 4. */
export const router = createBrowserRouter([
  {
    element: <AppLayout />,
    children: [
      { path: paths.home, element: <HomePage /> },
      { path: paths.feed, element: <FeedPage /> },
      { path: patterns.post, element: <PostPage /> },
      { path: patterns.authority, element: <AuthorityPage /> },
      { path: patterns.company, element: <CompanyPage /> },
      { path: patterns.price, element: <PricePage /> },
      { path: patterns.network, element: <NetworkPage /> },
      { path: paths.map, element: <MapPage /> },
      { path: paths.about, element: <AboutPage /> },
      { path: paths.adminLogin, element: <AdminPlaceholderPage /> },
      {
        element: <ProtectedRoute />,
        children: [
          { path: paths.admin, element: <AdminPlaceholderPage /> },
          { path: paths.adminPending, element: <AdminPlaceholderPage /> },
          { path: patterns.adminReview, element: <AdminPlaceholderPage /> },
          { path: paths.adminSources, element: <AdminPlaceholderPage /> },
        ],
      },
      { path: '*', element: <NotFoundPage /> },
    ],
  },
], {
  future: {
    v7_relativeSplatPath: true,
  },
});
