import { createBrowserRouter } from 'react-router-dom';
import { AppLayout } from '@/components/layout/AppLayout';
import { AboutPage } from '@/pages/AboutPage';
import { AuthorityPage } from '@/pages/AuthorityPage';
import { CalculatorPage } from '@/pages/CalculatorPage';
import { CompanyPage } from '@/pages/CompanyPage';
import { FeedPage } from '@/pages/FeedPage';
import { HomePage } from '@/pages/HomePage';
import { MapPage } from '@/pages/MapPage';
import { NetworkPage } from '@/pages/NetworkPage';
import { NotFoundPage } from '@/pages/NotFoundPage';
import { PostPage } from '@/pages/PostPage';
import { PricePage } from '@/pages/PricePage';
import { SearchPage } from '@/pages/SearchPage';
import { AdminDashboardPage } from '@/pages/admin/AdminDashboardPage';
import { AdminLoginPage } from '@/pages/admin/AdminLoginPage';
import { AdminPendingPage } from '@/pages/admin/AdminPendingPage';
import { AdminReviewPage } from '@/pages/admin/AdminReviewPage';
import { AdminSourcesPage } from '@/pages/admin/AdminSourcesPage';
import { AdminLayout } from '@/features/admin/AdminLayout';
import { AdminSection } from '@/features/admin/AdminSection';
import { ProtectedRoute } from './ProtectedRoute';
import { paths, patterns } from './paths';

/** The whole route tree under one shared AppLayout. Public routes are eager (small); the admin
 *  area sits behind ProtectedRoute → AdminLayout (Phase 4: login, review queue, sources). */
export const router = createBrowserRouter([
  {
    element: <AppLayout />,
    children: [
      { path: paths.home, element: <HomePage /> },
      { path: paths.feed, element: <FeedPage /> },
      { path: paths.search, element: <SearchPage /> },
      { path: patterns.post, element: <PostPage /> },
      { path: patterns.authority, element: <AuthorityPage /> },
      { path: patterns.company, element: <CompanyPage /> },
      { path: patterns.price, element: <PricePage /> },
      { path: patterns.network, element: <NetworkPage /> },
      { path: paths.map, element: <MapPage /> },
      { path: paths.calculator, element: <CalculatorPage /> },
      { path: paths.about, element: <AboutPage /> },
      {
        // The whole admin subtree (login + protected console) mounts AuthProvider here, so the
        // `/api/admin/me` session probe runs ONLY in the admin area — never on public pages,
        // which would self-blacklist against the IP-gated admin namespace (security.md §4).
        element: <AdminSection />,
        children: [
          { path: paths.adminLogin, element: <AdminLoginPage /> },
          {
            element: <ProtectedRoute />,
            children: [
              {
                element: <AdminLayout />,
                children: [
                  { path: paths.admin, element: <AdminDashboardPage /> },
                  { path: paths.adminPending, element: <AdminPendingPage /> },
                  { path: patterns.adminReview, element: <AdminReviewPage /> },
                  { path: paths.adminSources, element: <AdminSourcesPage /> },
                ],
              },
            ],
          },
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
