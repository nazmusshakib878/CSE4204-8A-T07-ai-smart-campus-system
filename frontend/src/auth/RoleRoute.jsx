import { Navigate, Outlet } from 'react-router-dom';
import { useAuth } from './auth-context';

const dashboardFor = (role) => {
  if (role === 'admin') return '/admin';
  if (role === 'faculty') return '/faculty-dashboard';
  return '/dashboard';
};

function RoleRoute({ allowedRoles }) {
  const { user } = useAuth();

  if (!user || !allowedRoles.includes(user.role)) {
    return <Navigate to={dashboardFor(user?.role)} replace />;
  }

  return <Outlet />;
}

export default RoleRoute;
