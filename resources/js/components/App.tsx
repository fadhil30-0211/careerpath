import { BrowserRouter, Route, Routes } from 'react-router-dom';
import { AdminLayout } from './layout/AdminLayout';
import { AppLayout, PublicLayout as PublicSiteLayout, RequireAdmin, RequireAuth } from './layout/Layouts';
import { AuthProvider } from '../features/auth/AuthContext';
import { CatalogProvider } from '../features/catalog/CatalogContext';
import { AdminAuditPage } from '../pages/admin/AdminAuditPage';
import { AdminDashboardPage } from '../pages/admin/AdminDashboardPage';
import { AdminRequirementsPage } from '../pages/admin/AdminRequirementsPage';
import { AdminResourcePage } from '../pages/admin/AdminResourcePage';
import { AdminReviewsPage } from '../pages/admin/AdminReviewsPage';
import { AdminRoadmapPage } from '../pages/admin/AdminRoadmapPage';
import { AdminUsersPage } from '../pages/admin/AdminUsersPage';
import { AuthPage } from '../pages/AuthPage';
import { DashboardPage } from '../pages/DashboardPage';
import { DevelopmentPage } from '../pages/DevelopmentPage';
import { ExplorePage } from '../pages/ExplorePage';
import { HistoryPage } from '../pages/HistoryPage';
import { HomePage } from '../pages/HomePage';
import { HowItWorksPage } from '../pages/HowItWorksPage';
import { MatchingPage, MatchingResultPage } from '../pages/MatchingPage';
import { NotFoundPage } from '../pages/NotFoundPage';
import { OnboardingPage } from '../pages/OnboardingPage';
import { ProfessionDetailPage } from '../pages/ProfessionDetailPage';
import { ProfessionsPage } from '../pages/ProfessionsPage';
import { ProfilePage } from '../pages/ProfilePage';
import { RoadmapDetailPage, RoadmapsPage } from '../pages/RoadmapsPage';
import { SavedPage } from '../pages/SavedPage';

export default function App() {
    return <BrowserRouter><AuthProvider><CatalogProvider><Routes>
        <Route element={<PublicSiteLayout />}>
            <Route index element={<HomePage />} />
            <Route path="professions" element={<ProfessionsPage />} />
            <Route path="professions/:id" element={<ProfessionDetailPage />} />
            <Route path="explore" element={<ExplorePage />} />
            <Route path="how-it-works" element={<HowItWorksPage />} />
        </Route>
        <Route path="login" element={<AuthPage mode="login" />} />
        <Route path="register" element={<AuthPage mode="register" />} />
        <Route element={<RequireAuth />}>
            <Route path="onboarding" element={<OnboardingPage />} />
            <Route element={<AppLayout />}>
                <Route path="dashboard" element={<DashboardPage />} />
                <Route path="matching" element={<MatchingPage />} />
                <Route path="matching/:id" element={<MatchingResultPage />} />
                <Route path="development" element={<DevelopmentPage />} />
                <Route path="saved" element={<SavedPage />} />
                <Route path="history" element={<HistoryPage />} />
                <Route path="roadmaps" element={<RoadmapsPage />} />
                <Route path="roadmaps/:id" element={<RoadmapDetailPage />} />
                <Route path="profile" element={<ProfilePage />} />
            </Route>
            <Route element={<RequireAdmin />}>
                <Route path="admin" element={<AdminLayout />}>
                    <Route index element={<AdminDashboardPage />} />
                    <Route path="resources/:resource" element={<AdminResourcePage />} />
                    <Route path="occupations/:id/requirements" element={<AdminRequirementsPage />} />
                    <Route path="roadmaps/:id" element={<AdminRoadmapPage />} />
                    <Route path="reviews" element={<AdminReviewsPage />} />
                    <Route path="users" element={<AdminUsersPage />} />
                    <Route path="audit-logs" element={<AdminAuditPage />} />
                </Route>
            </Route>
        </Route>
        <Route path="*" element={<NotFoundPage />} />
    </Routes></CatalogProvider></AuthProvider></BrowserRouter>;
}
