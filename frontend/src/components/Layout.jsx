import { Link, NavLink } from 'react-router-dom';

function Layout({ children, title, subtitle }) {
  const navItems = [
    { to: '/', label: 'Home' },
    { to: '/dashboard', label: 'Dashboard' },
    { to: '/functions', label: 'Functions' },
    { to: '/ai-assistant', label: 'AI Assistant' },
    { to: '/admin', label: 'Admin' }
  ];

  return (
    <div className="app-shell">
      <nav className="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div className="container py-2">
          <Link className="navbar-brand fw-bold text-primary d-flex align-items-center gap-2" to="/">
            <span className="rounded-circle d-inline-flex align-items-center justify-content-center text-white" style={{ width: 42, height: 42, background: 'linear-gradient(135deg, #2563eb, #0f172a)' }}>
              🎓
            </span>
            <span>NUBTK Campus</span>
          </Link>

          <div className="d-flex flex-wrap align-items-center gap-3">
            {navItems.map((item) => (
              <NavLink key={item.to} to={item.to} className="nav-link text-secondary">
                {item.label}
              </NavLink>
            ))}
            <Link to="/login" className="btn btn-outline-secondary rounded-pill px-3">Login</Link>
            <Link to="/register" className="btn btn-primary rounded-pill px-3">Register</Link>
          </div>
        </div>
      </nav>

      <main className="container py-4 py-lg-5">
        {(title || subtitle) && (
          <div className="mb-4">
            {title && <h2 className="fw-bold text-dark mb-2">{title}</h2>}
            {subtitle && <p className="text-secondary mb-0">{subtitle}</p>}
          </div>
        )}
        {children}
      </main>

      <footer className="bg-white border-top py-4 mt-4">
        <div className="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
          <small className="text-secondary">© 2026 Northern University of Business and Technology, Khulna</small>
          <small className="text-secondary">Education Management System • React + Bootstrap</small>
        </div>
      </footer>
    </div>
  );
}

export default Layout;
