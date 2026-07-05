import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useAuth } from './auth-context';
import { LoadingState } from '../components/Feedback';

function ProtectedRoute() {
  const location = useLocation();
  const { initializing, isAuthenticated } = useAuth();

  if (initializing) {
    return <LoadingState message="Checking your session..." fullPage />;
  }

  if (!isAuthenticated) {
    return (
      <Navigate
        to="/login"
        replace
        state={{
          from: `${location.pathname}${location.search}${location.hash}`,
          flash: {
            variant: 'warning',
            message: 'Please sign in to access that page.',
          },
        }}
      />
    );
  }

  return <Outlet />;
}

export default ProtectedRoute;
