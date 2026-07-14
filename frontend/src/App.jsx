import React from 'react';
import 'bootstrap/dist/css/bootstrap.min.css';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import HomePage from './pages/HomePage';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import DashboardPage from './pages/DashboardPage';
import ProfilePage from './pages/ProfilePage';
import FunctionsPage from './pages/FunctionsPage';
import AiAssistantPage from './pages/AiAssistantPage';
import CourseRecommendationsPage from './pages/CourseRecommendationsPage';
import FacultyDashboardPage from './pages/FacultyDashboardPage';
import StudentMonitoringPage from './pages/StudentMonitoringPage';
import RiskAlertsPage from './pages/RiskAlertsPage';
import AdminPage from './pages/AdminPage';
import AcademicManagementPage from './pages/AcademicManagementPage';
import ManageUsersPage from './pages/ManageUsersPage';
import ManageNoticesPage from './pages/ManageNoticesPage';
import ManageDepartmentsPage from './pages/ManageDepartmentsPage';
import NoticeInboxPage from './pages/NoticeInboxPage';
import NotFoundPage from './pages/NotFoundPage';
import CampusServicesPage from './pages/CampusServicesPage';
import { AuthProvider } from './auth/AuthContext';
import ProtectedRoute from './auth/ProtectedRoute';
import RoleRoute from './auth/RoleRoute';
import { useAuth } from './auth/auth-context';

function DashboardEntry() {
  const { user } = useAuth();
  if (user?.role === 'admin') return <AdminPage />;
  return user?.role === 'faculty' ? <FacultyDashboardPage /> : <DashboardPage />;
}

function App() {
  return (
    <Router>
      <AuthProvider>
        <Routes>
          <Route path="/" element={<HomePage />} />
          <Route path="/login" element={<LoginPage />} />
          <Route path="/register" element={<RegisterPage />} />

          <Route element={<ProtectedRoute />}>
            <Route path="/dashboard" element={<DashboardEntry />} />
            <Route path="/profile" element={<ProfilePage />} />
            <Route path="/messages" element={<NoticeInboxPage />} />
            <Route path="/campus-services" element={<CampusServicesPage />} />
            <Route element={<RoleRoute allowedRoles={['student']} />}>
              <Route path="/functions" element={<FunctionsPage />} />
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
              <Route path="/admin/notices" element={<ManageNoticesPage />} />
              <Route path="/admin/departments" element={<ManageDepartmentsPage />} />
            </Route>
          </Route>

          <Route path="*" element={<NotFoundPage />} />
        </Routes>
      </AuthProvider>
    </Router>
  );
}

export default App;