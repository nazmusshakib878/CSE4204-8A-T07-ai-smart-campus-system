import { useEffect, useState } from 'react';
import { Link, NavLink, useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '../auth/auth-context';
import { StatusAlert } from './Feedback';

const iconPaths = {
  home: 'M3 10.5 12 3l9 7.5M5 9v11h5v-6h4v6h5V9',
  dashboard: 'M4 4h6v6H4V4Zm10 0h6v10h-6V4ZM4 14h6v6H4v-6Zm10 4h6v2h-6v-2Z',
  functions: 'M4 5h16M4 12h16M4 19h16M7 3v4m10 3v4M9 17v4',
  assistant: 'M12 3 10.7 7.1a5 5 0 0 1-3.2 3.2L3.5 12l4 1.7a5 5 0 0 1 3.2 3.2L12 21l1.7-4.1a5 5 0 0 1 3.2-3.2L21 12l-4.1-1.7a5 5 0 0 1-3.2-3.2L12 3Z',
  admin: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87m0-7.26a4 4 0 0 1 0 7.75',
  profile: 'M20 21a8 8 0 0 0-16 0m8-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z',
};

function NavigationIcon({ name }) {
  return (
    <svg className="navigation-icon" viewBox="0 0 24 24" aria-hidden="true">
      <path d={iconPaths[name]} />
    </svg>
  );
}

function Brand() {
  return (
    <Link className="navbar-brand fw-bold text-primary d-flex align-items-center gap-2 m-0" to="/">
      <span className="brand-mark rounded-circle d-inline-flex align-items-center justify-content-center text-white" aria-hidden="true">
        <svg viewBox="0 0 24 24">
          <path d="M2 8.5 12 3l10 5.5-10 5.5L2 8.5Zm4 3.2V16c0 1.8 2.7 3.5 6 3.5s6-1.7 6-3.5v-4.3L12 15l-6-3.3Zm14 0v5.8h2V10.6l-2 1.1Z" />
        </svg>
      </span>
      <span className="brand-text">NUBTK Campus</span>
    </Link>
  );
}

function Layout({ children, title, subtitle }) {
  const location = useLocation();
  const navigate = useNavigate();
  const { isAuthenticated, logout, user } = useAuth();
  const [menuOpen, setMenuOpen] = useState(false);
  const [loggingOut, setLoggingOut] = useState(false);
  const [flash, setFlash] = useState(null);
  const publicNavItems = [
    { to: '/', label: 'Home' },
    ...(isAuthenticated ? [{ to: '/dashboard', label: 'Dashboard' }] : []),
  ];
  const appNavItems = [
    { to: '/dashboard', label: 'Dashboard', icon: 'dashboard' },
    { to: '/functions', label: 'Campus Tools', icon: 'functions' },
    { to: '/ai-assistant', label: 'AI Assistant', icon: 'assistant' },
    ...(user?.role === 'admin' ? [{ to: '/admin', label: 'Administration', icon: 'admin' }] : []),
  ];
  const internalRoutes = ['/dashboard', '/profile', '/functions', '/ai-assistant', '/admin'];
  const useInternalLayout = isAuthenticated
    && internalRoutes.some((route) => location.pathname.startsWith(route));
  const currentSection = location.pathname === '/profile'
    ? 'My Profile'
    : appNavItems.find((item) => location.pathname.startsWith(item.to))?.label || 'Workspace';
  const userInitials = user?.name
    ?.split(' ')
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase() || 'US';

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

  useEffect(() => {
    const incomingFlash = location.state?.flash;
    if (!incomingFlash) return;

    setFlash(incomingFlash);

    const nextState = { ...location.state };
    delete nextState.flash;
    navigate(`${location.pathname}${location.search}${location.hash}`, {
      replace: true,
      state: nextState,
    });
  }, [location.hash, location.pathname, location.search, location.state, navigate]);

  useEffect(() => {
    if (!flash) return undefined;

    const timeout = window.setTimeout(() => setFlash(null), 6000);
    return () => window.clearTimeout(timeout);
  }, [flash]);

  const handleLogout = async () => {
    setLoggingOut(true);

    try {
      await logout();
      navigate('/login', {
        replace: true,
        state: {
          flash: {
            variant: 'success',
            message: 'You have been logged out successfully.',
          },
        },
      });
    } finally {
      setLoggingOut(false);
    }
  };

  const flashMessage = flash && (
    <StatusAlert
      variant={flash.variant}
      message={flash.message}
      onDismiss={() => setFlash(null)}
    />
  );

  const pageHeading = (title || subtitle) && (
    <div className="page-heading mb-4">
      {title && <h2 className="page-title fw-bold text-dark mb-2">{title}</h2>}
      {subtitle && <p className="text-secondary mb-0">{subtitle}</p>}
    </div>
  );

  if (useInternalLayout) {
    return (
      <div className="app-shell app-shell-internal">
        <aside id="app-sidebar" className={`app-sidebar${menuOpen ? ' is-open' : ''}`}>
          <div className="app-sidebar-header">
            <Brand />
            <button
              type="button"
              className="btn-close d-lg-none"
              aria-label="Close navigation"
              onClick={() => setMenuOpen(false)}
            />
          </div>

          <div className="sidebar-user-card">
            <span className="user-avatar" aria-hidden="true">{userInitials}</span>
            <div className="min-w-0">
              <strong>{user?.name || 'Campus User'}</strong>
              <span>{user?.role || 'Member'}</span>
            </div>
          </div>

          <nav className="app-sidebar-nav" aria-label="Application navigation">
            <span className="sidebar-section-label">WORKSPACE</span>
            {appNavItems.map((item) => (
              <NavLink
                key={item.to}
                to={item.to}
                className={({ isActive }) => `app-nav-link${isActive ? ' active' : ''}`}
              >
                <NavigationIcon name={item.icon} />
                <span>{item.label}</span>
              </NavLink>
            ))}
            <NavLink
              to="/profile"
              className={({ isActive }) => `app-nav-link${isActive ? ' active' : ''}`}
            >
              <NavigationIcon name="profile" />
              <span>My Profile</span>
            </NavLink>
          </nav>

          <div className="app-sidebar-footer">
            <Link to="/" className="app-nav-link">
              <NavigationIcon name="home" />
              <span>Public Home</span>
            </Link>
            <button
              type="button"
              className="sidebar-logout-button"
              onClick={handleLogout}
              disabled={loggingOut}
              aria-busy={loggingOut}
            >
              {loggingOut && <span className="spinner-border spinner-border-sm" aria-hidden="true" />}
              <span>{loggingOut ? 'Logging out...' : 'Log out'}</span>
            </button>
          </div>
        </aside>

        {menuOpen && (
          <button
            type="button"
            className="sidebar-backdrop d-lg-none"
            aria-label="Close navigation"
            onClick={() => setMenuOpen(false)}
          />
        )}

        <div className="app-workspace">
          <header className="app-topbar">
            <button
              type="button"
              className="app-menu-button d-lg-none"
              aria-controls="app-sidebar"
              aria-expanded={menuOpen}
              aria-label="Open navigation"
              onClick={() => setMenuOpen(true)}
            >
              <span />
              <span />
              <span />
            </button>
            <div className="app-topbar-title">
              <span>Education Management System</span>
              <strong>{currentSection}</strong>
            </div>
            <Link to="/profile" className="topbar-profile" aria-label="Open your profile">
              <span className="topbar-profile-copy">
                <strong>{user?.name || 'Campus User'}</strong>
                <small>{user?.email || ''}</small>
              </span>
              <span className="user-avatar user-avatar-small" aria-hidden="true">{userInitials}</span>
            </Link>
          </header>

          <main className="app-content">
            {pageHeading}
            {flashMessage}
            {children}
          </main>
        </div>
      </div>
    );
  }

  return (
    <div className="app-shell">
      <nav className="navbar navbar-light bg-white shadow-sm sticky-top" aria-label="Primary navigation">
        <div className="container py-2 flex-wrap">
          <Brand />

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
            {publicNavItems.map((item) => (
              <NavLink
                key={item.to}
                to={item.to}
                end={item.to === '/'}
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
                  {loggingOut && <span className="spinner-border spinner-border-sm me-2" aria-hidden="true" />}
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
        {pageHeading}
        {flashMessage}
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
