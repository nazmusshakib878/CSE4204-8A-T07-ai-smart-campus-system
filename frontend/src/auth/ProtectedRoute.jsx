import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useAuth } from './auth-context';

function ProtectedRoute() {
  const location = useLocation();
  const { initializing, isAuthenticated } = useAuth();

  if (initializing) {
    return (
      <div className="min-vh-100 d-flex align-items-center justify-content-center text-secondary">
        Checking your session...
      </div>
    );
  }

  if (!isAuthenticated) {
    return (
      <Navigate
        to="/login"
        replace
        state={{ from: `${location.pathname}${location.search}${location.hash}` }}
      />
    );
  }

  return <Outlet />;
}

export default ProtectedRoute;
