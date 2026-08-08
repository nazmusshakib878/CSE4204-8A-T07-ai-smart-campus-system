import { lazy, Suspense } from 'react';
import 'bootstrap/dist/css/bootstrap.min.css';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { AuthProvider } from './auth/AuthContext';
import ProtectedRoute from './auth/ProtectedRoute';
import RoleRoute from './auth/RoleRoute';
import { useAuth } from './auth/auth-context';
import { LoadingState } from './components/Feedback';

const HomePage = lazy(() => import('./pages/HomePage'));
const LoginPage = lazy(() => import('./pages/LoginPage'));
const ForgotPasswordPage = lazy(() => import('./pages/ForgotPasswordPage'));
const ResetPasswordPage = lazy(() => import('./pages/ResetPasswordPage'));
const RegisterPage = lazy(() => import('./pages/RegisterPage'));
const DashboardPage = lazy(() => import('./pages/DashboardPage'));
const ProfilePage = lazy(() => import('./pages/ProfilePage'));
const FunctionsPage = lazy(() => import('./pages/FunctionsPage'));
const AiAssistantPage = lazy(() => import('./pages/AiAssistantPage'));
const CourseRecommendationsPage = lazy(() => import('./pages/CourseRecommendationsPage'));
const FacultyDashboardPage = lazy(() => import('./pages/FacultyDashboardPage'));
const StudentMonitoringPage = lazy(() => import('./pages/StudentMonitoringPage'));
const RiskAlertsPage = lazy(() => import('./pages/RiskAlertsPage'));
const AdminPage = lazy(() => import('./pages/AdminPage'));
const AcademicManagementPage = lazy(() => import('./pages/AcademicManagementPage'));
const ManageUsersPage = lazy(() => import('./pages/ManageUsersPage'));
const AllUsersPage = lazy(() => import('./pages/AllUsersPage'));
const ManageNoticesPage = lazy(() => import('./pages/ManageNoticesPage'));
const ManageDepartmentsPage = lazy(() => import('./pages/ManageDepartmentsPage'));
const NoticeInboxPage = lazy(() => import('./pages/NoticeInboxPage'));
const NotFoundPage = lazy(() => import('./pages/NotFoundPage'));
const CampusServicesPage = lazy(() => import('./pages/CampusServicesPage'));

function DashboardEntry() {
  const { user } = useAuth();
  if (user?.role === 'admin') return <AdminPage />;
  return user?.role === 'faculty' ? <FacultyDashboardPage /> : <DashboardPage />;
}

function App() {
  return (
    <Router>
      <AuthProvider>
        <Suspense fallback={<LoadingState message="Loading page..." fullPage />}>
          <Routes>
          <Route path="/" element={<HomePage />} />
          <Route path="/login" element={<LoginPage />} />
          <Route path="/forgot-password" element={<ForgotPasswordPage />} />
          <Route path="/reset-password" element={<ResetPasswordPage />} />
          <Route path="/register" element={<RegisterPage />} />

          <Route element={<ProtectedRoute />}>
            <Route path="/dashboard" element={<DashboardEntry />} />
            <Route path="/profile" element={<ProfilePage />} />
            <Route path="/messages" element={<NoticeInboxPage />} />
            <Route path="/campus-services" element={<CampusServicesPage />} />
            <Route element={<RoleRoute allowedRoles={['student', 'faculty', 'admin']} />}>
              <Route path="/functions" element={<FunctionsPage />} />
            </Route>
            <Route element={<RoleRoute allowedRoles={['student']} />}>
              <Route path="/ai-assistant" element={<AiAssistantPage />} />
              <Route path="/course-recommendations" element={<CourseRecommendationsPage />} />
            </Route>
            <Route element={<RoleRoute allowedRoles={['faculty']} />}>
              <Route path="/faculty-dashboard" element={<FacultyDashboardPage />} />
            </Route>
            <Route element={<RoleRoute allowedRoles={['faculty', 'admin']} />}>
              <Route path="/notices/manage" element={<ManageNoticesPage />} />
              <Route path="/student-monitoring" element={<StudentMonitoringPage />} />
              <Route path="/academic-management" element={<AcademicManagementPage />} />
              <Route path="/risk-alerts" element={<RiskAlertsPage />} />
            </Route>
            <Route element={<RoleRoute allowedRoles={['admin']} />}>
              <Route path="/admin" element={<AdminPage />} />
              <Route path="/admin/users" element={<ManageUsersPage />} />
              <Route path="/admin/all-users" element={<AllUsersPage />} />
              <Route path="/admin/notices" element={<ManageNoticesPage />} />
              <Route path="/admin/departments" element={<ManageDepartmentsPage />} />
            </Route>
          </Route>

          <Route path="*" element={<NotFoundPage />} />
          </Routes>
        </Suspense>
      </AuthProvider>
    </Router>
  );
}

export default App;
