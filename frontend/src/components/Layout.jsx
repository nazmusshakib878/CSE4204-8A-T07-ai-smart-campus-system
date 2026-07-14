import { useEffect, useState } from 'react';
import { Link, NavLink, useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '../auth/auth-context';
import { StatusAlert } from './Feedback';
import { getDashboardPath } from '../utils/routes';
import { getNotices } from '../services/api';

const CAMPUS_LOGO_URL = 'https://nubtkhulna.ac.bd/ter/assets/img/adminica_logo_blue-trans.png';
const getProfilePhotoKey = (profile) => {
  const owner = profile?.id || profile?.email || profile?.student_id || profile?.varsity_id || profile?.university_id;
  return owner ? `profile_photo_${owner}` : '';
};

const iconPaths = {
  home: 'M3 10.5 12 3l9 7.5M5 9v11h5v-6h4v6h5V9',
  dashboard: 'M4 4h6v6H4V4Zm10 0h6v10h-6V4ZM4 14h6v6H4v-6Zm10 4h6v2h-6v-2Z',
  functions: 'M4 5h16M4 12h16M4 19h16M7 3v4m10 3v4M9 17v4',
  assistant: 'M12 3 10.7 7.1a5 5 0 0 1-3.2 3.2L3.5 12l4 1.7a5 5 0 0 1 3.2 3.2L12 21l1.7-4.1a5 5 0 0 1 3.2-3.2L21 12l-4.1-1.7a5 5 0 0 1-3.2-3.2L12 3Z',
  recommendations: 'M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H7a3 3 0 0 0-3 3V5.5Zm0 0V21m4-13h8m-8 4h8m-8 4h5',
  monitoring: 'M4 19V9m5 10V5m5 14v-7m5 7V3',
  academic: 'M4 5h16v14H4V5Zm4 4h8m-8 4h8m-8 4h5',
  risk: 'M12 3 22 20H2L12 3Zm0 6v5m0 3h.01',
  admin: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87m0-7.26a4 4 0 0 1 0 7.75',
  users: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm11 10v-2a3 3 0 0 0-2-2.83M16 3.13a4 4 0 0 1 0 7.75',
  notices: 'M7 3h8l4 4v14H7V3Zm8 0v5h5M9 13h8M9 17h8M9 9h3',
  departments: 'M4 4h16v5H4V4Zm0 7h7v9H4v-9Zm9 0h7v9h-7v-9Z',
  messages: 'M4 5h16v12H7l-3 3V5Zm4 4h8m-8 4h5',
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
      <span className="brand-mark d-inline-flex align-items-center justify-content-center" aria-hidden="true">
        <img src={CAMPUS_LOGO_URL} alt="" />
      </span>
      <span className="brand-text">NUBTK Campus</span>
    </Link>
  );
}

function UserAvatar({ photo, initials, small = false }) {
  return (
    <span className={`user-avatar${small ? ' user-avatar-small' : ''}`} aria-hidden="true">
      {photo ? <img src={photo} alt="" /> : initials}
    </span>
  );
}

function Layout({ children, title, subtitle }) {
  const location = useLocation();
  const navigate = useNavigate();
  const { isAuthenticated, logout, user } = useAuth();
  const [menuOpen, setMenuOpen] = useState(false);
  const [loggingOut, setLoggingOut] = useState(false);
  const [flash, setFlash] = useState(null);
  const [profilePhoto, setProfilePhoto] = useState('');
  const [unreadNotices, setUnreadNotices] = useState(0);
  const isFacultyUser = user?.role === 'faculty';
  const isAdminUser = user?.role === 'admin';
  const publicNavItems = [
    { to: '/', label: 'Home' },
    ...(isAuthenticated ? [{ to: getDashboardPath(user), label: 'Dashboard' }] : []),
  ];
  const studentNavItems = [
    { to: '/dashboard', label: 'Dashboard', icon: 'dashboard' },
    { to: '/functions', label: 'Campus Tools', icon: 'functions' },
    { to: '/ai-assistant', label: 'AI Assistant', icon: 'assistant' },
    { to: '/course-recommendations', label: 'Course Recommendations', icon: 'recommendations' },
    { to: '/messages', label: unreadNotices > 0 ? `Messages (${unreadNotices})` : 'Messages', icon: 'messages' },
  ];
  const facultyNavItems = [
    { to: '/faculty-dashboard', label: 'Faculty Dashboard', icon: 'dashboard' },
    { to: '/student-monitoring', label: 'Student Monitoring', icon: 'monitoring' },
    { to: '/academic-management', label: 'Academic Data', icon: 'academic' },
    { to: '/risk-alerts', label: 'Risk Alerts', icon: 'risk' },
    { to: '/notices/manage', label: 'Send Notices', icon: 'notices' },
    { to: '/messages', label: unreadNotices > 0 ? `Messages (${unreadNotices})` : 'Messages', icon: 'messages' },
  ];
  const adminNavItems = [
    { to: '/admin', label: 'Dashboard', icon: 'dashboard' },
    { to: '/admin/users', label: 'Manage Users', icon: 'users' },
    { to: '/academic-management', label: 'Academic Data', icon: 'academic' },
    { to: '/admin/notices', label: 'Manage Notices', icon: 'notices' },
    { to: '/admin/departments', label: 'Manage Departments', icon: 'departments' },
    { to: '/messages', label: unreadNotices > 0 ? `Messages (${unreadNotices})` : 'Messages', icon: 'messages' },
  ];
  const appNavItems = isAdminUser ? adminNavItems : isFacultyUser ? facultyNavItems : studentNavItems;
  const internalRoutes = ['/dashboard', '/profile', '/functions', '/ai-assistant', '/course-recommendations', '/messages', '/notices/manage', '/faculty-dashboard', '/student-monitoring', '/academic-management', '/risk-alerts', '/admin'];
  const useInternalLayout = isAuthenticated
    && internalRoutes.some((route) => location.pathname.startsWith(route));
  const currentSection = location.pathname === '/profile'
    ? 'My Profile'
    : [...appNavItems].sort((a, b) => b.to.length - a.to.length).find((item) => location.pathname.startsWith(item.to))?.label || 'Workspace';
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
    if (!isAuthenticated || !user) {
      setUnreadNotices(0);
      return undefined;
    }

    let active = true;
    const readKey = `notice_read_ids_${user.id || user.email || 'guest'}`;
    const refreshUnread = () => {
      getNotices()
        .then((response) => {
          if (!active) return;
          const notices = response.data.data || [];
          const readIds = JSON.parse(localStorage.getItem(readKey) || '[]');
          setUnreadNotices(notices.filter((notice) => !readIds.includes(String(notice.id))).length);
        })
        .catch(() => {
          if (active) setUnreadNotices(0);
        });
    };

    refreshUnread();
    window.addEventListener('notice-read-updated', refreshUnread);
    return () => {
      active = false;
      window.removeEventListener('notice-read-updated', refreshUnread);
    };
  }, [isAuthenticated, user]);

  useEffect(() => {
    const profilePhotoKey = getProfilePhotoKey(user);
    setProfilePhoto(profilePhotoKey ? localStorage.getItem(profilePhotoKey) || '' : '');

    const syncProfilePhoto = (event) => {
      if (!profilePhotoKey) {
        setProfilePhoto('');
        return;
      }

      if (event.type === 'profile-photo-updated' && event.detail?.key !== profilePhotoKey) return;
      setProfilePhoto(localStorage.getItem(profilePhotoKey) || event.detail?.photo || '');
    };

    window.addEventListener('profile-photo-updated', syncProfilePhoto);
    window.addEventListener('storage', syncProfilePhoto);
    return () => {
      window.removeEventListener('profile-photo-updated', syncProfilePhoto);
      window.removeEventListener('storage', syncProfilePhoto);
    };
  }, [user]);

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
            <UserAvatar photo={profilePhoto} initials={userInitials} />
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
              <UserAvatar photo={profilePhoto} initials={userInitials} small />
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
          <small className="text-secondary">Â© 2026 Northern University of Business and Technology, Khulna</small>
        </div>
      </footer>
    </div>
  );
}

export default Layout;