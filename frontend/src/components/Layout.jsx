import { useEffect, useState } from 'react';
import { Link, NavLink, useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '../auth/auth-context';

function Layout({ children, title, subtitle }) {
  const location = useLocation();
  const navigate = useNavigate();
  const { isAuthenticated, logout, user } = useAuth();
  const [menuOpen, setMenuOpen] = useState(false);
  const [loggingOut, setLoggingOut] = useState(false);
  const navItems = [
    { to: '/', label: 'Home' },
    { to: '/dashboard', label: 'Dashboard' },
    { to: '/functions', label: 'Functions' },
    { to: '/ai-assistant', label: 'AI Assistant' },
    { to: '/admin', label: 'Admin' }
  ];

  useEffect(() => {
    setMenuOpen(false);
  }, [location.pathname]);

  useEffect(() => {
    if (!menuOpen) return undefined;

    const closeOnEscape = (event) => {
      if (event.key === 'Escape') setMenuOpen(false);
    };

    window.addEventListener('keydown', closeOnEscape);
    return () => window.removeEventListener('keydown', closeOnEscape);
  }, [menuOpen]);

  const handleLogout = async () => {
    setLoggingOut(true);

    try {
      await logout();
      navigate('/login', { replace: true });
    } finally {
      setLoggingOut(false);
    }
  };

  return (
    <div className="app-shell">
      <nav className="navbar navbar-light bg-white shadow-sm sticky-top" aria-label="Primary navigation">
        <div className="container py-2 flex-wrap">
          <Link className="navbar-brand fw-bold text-primary d-flex align-items-center gap-2 me-2" to="/">
            <span className="brand-mark rounded-circle d-inline-flex align-items-center justify-content-center text-white" aria-hidden="true">
              <svg viewBox="0 0 24 24" role="img">
                <path d="M2 8.5 12 3l10 5.5-10 5.5L2 8.5Zm4 3.2V16c0 1.8 2.7 3.5 6 3.5s6-1.7 6-3.5v-4.3L12 15l-6-3.3Zm14 0v5.8h2V10.6l-2 1.1Z" />
              </svg>
            </span>
            <span className="brand-text">NUBTK Campus</span>
          </Link>

          <button
            type="button"
            className="navbar-toggler d-lg-none"
            aria-controls="primary-navigation"
            aria-expanded={menuOpen}
            aria-label={menuOpen ? 'Close navigation menu' : 'Open navigation menu'}
            onClick={() => setMenuOpen((open) => !open)}
          >
            <span className="navbar-toggler-icon" />
          </button>

          <div
            id="primary-navigation"
            className={`site-navigation${menuOpen ? ' is-open' : ''}`}
          >
            {navItems.map((item) => (
              <NavLink
                key={item.to}
                to={item.to}
                className={({ isActive }) => `nav-link${isActive ? ' active' : ''}`}
              >
                {item.label}
              </NavLink>
            ))}
            {isAuthenticated ? (
              <div className="nav-actions">
                <Link to="/profile" className="btn btn-outline-secondary rounded-pill px-3 profile-link">
                  {user?.name || 'Profile'}
                </Link>
                <button
                  type="button"
                  className="btn btn-primary rounded-pill px-3"
                  onClick={handleLogout}
                  disabled={loggingOut}
                  aria-busy={loggingOut}
                >
                  {loggingOut ? 'Logging out...' : 'Logout'}
                </button>
              </div>
            ) : (
              <div className="nav-actions">
                <Link to="/login" className="btn btn-outline-secondary rounded-pill px-3">Login</Link>
                <Link to="/register" className="btn btn-primary rounded-pill px-3">Register</Link>
              </div>
            )}
          </div>
        </div>
      </nav>

      <main className="app-main container py-4 py-lg-5">
        {(title || subtitle) && (
          <div className="page-heading mb-4">
            {title && <h2 className="page-title fw-bold text-dark mb-2">{title}</h2>}
            {subtitle && <p className="text-secondary mb-0">{subtitle}</p>}
          </div>
        )}
        {children}
      </main>

      <footer className="bg-white border-top py-4 mt-4">
        <div className="footer-content container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
          <small className="text-secondary">© 2026 Northern University of Business and Technology, Khulna</small>
          <small className="text-secondary">Education Management System • React + Bootstrap</small>
        </div>
      </footer>
    </div>
  );
}

export default Layout;
