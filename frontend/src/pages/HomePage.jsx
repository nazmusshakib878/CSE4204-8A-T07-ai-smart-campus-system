import { Link } from 'react-router-dom';
import Layout from '../components/Layout';
import campusImg from '../assets/ChatGPT Image Jul 4, 2026, 11_22_37 PM.png';
import { useAuth } from '../auth/auth-context';
import { getDashboardPath } from '../utils/routes';

function HomePage() {
  const { isAuthenticated, user } = useAuth();
  const dashboardPath = getDashboardPath(user);
  const overview = user?.role === 'faculty'
    ? {
        title: 'Teaching Overview',
        leftLabel: 'Active Classes',
        leftValue: '8',
        rightLabel: 'Avg. Attendance',
        rightValue: '83%',
      }
    : user?.role === 'admin'
      ? {
          title: 'System Overview',
          leftLabel: 'Active Students',
          leftValue: '8,400+',
          rightLabel: 'System Alerts',
          rightValue: '7',
        }
      : {
          title: 'Academic Overview',
          leftLabel: 'Average CGPA',
          leftValue: '3.74',
          rightLabel: 'Overall Attendance',
          rightValue: '92.4%',
        };

  const features = [
    { title: 'Attendance Tracking', desc: 'Real-time updates on class participation and absence patterns.', icon: 'AT' },
    { title: 'Academic Insights', desc: 'Clear view of marks, GPA trends, and performance summaries.', icon: 'AI' },
    { title: 'AI Study Support', desc: 'Smart guidance and academic recommendations 24/7.', icon: 'SS' },
  ];

  return (
    <Layout title="Home" subtitle="Welcome to NUBTK Smart Campus">
      <section className="home-hero rounded-4 shadow-sm p-4 p-lg-5 mb-5 mt-3" style={{ background: 'linear-gradient(145deg, #f8fafc, #e2e8f0)' }}>
        <div className="row align-items-center g-5">
          <div className="col-lg-7">
            <div className="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 mb-4 border border-primary-subtle">
              NUBTK Smart Campus Platform
            </div>

            <h1 className="display-4 mb-3" style={{ fontWeight: 800, color: '#1e293b', lineHeight: '1.2' }}>
              Northern University of <br />
              <span style={{ color: '#2563eb' }}>Business & Technology | Khulna</span>
            </h1>

            <p className="lead text-secondary mb-5 fs-4">
              Smarter campus operations, attendance, and student progress from one unified platform.
            </p>

            <div className="d-flex flex-wrap gap-3 mb-5">
              {!isAuthenticated && (
                <Link to="/register" className="btn btn-primary btn-lg rounded-pill px-5 shadow-sm fw-bold">Create Account</Link>
              )}
              <Link to={isAuthenticated ? dashboardPath : '/login'} className="btn btn-white btn-lg rounded-pill px-5 bg-white shadow-sm border fw-bold text-dark">
                {isAuthenticated ? 'View Dashboard' : 'Sign In'}
              </Link>
            </div>

            <div className="d-flex gap-4">
              <div>
                <h3 className="fw-bold mb-0 text-dark">8,400+</h3>
                <span className="text-muted small fw-semibold">Active Students</span>
              </div>
              <div className="border-start ps-4 border-2">
                <h3 className="fw-bold mb-0 text-dark">24/7</h3>
                <span className="text-muted small fw-semibold">AI Support Available</span>
              </div>
            </div>
          </div>

          <div className="col-lg-5">
            <div className="card border-0 shadow-lg rounded-4 overflow-hidden position-relative">
              <img src={campusImg} alt="NUBTK Campus" className="img-fluid w-100" style={{ height: '420px', objectFit: 'cover' }} />

              {isAuthenticated && (
                <div className="position-absolute bottom-0 start-0 w-100 p-3 p-lg-4" style={{ background: 'linear-gradient(to top, rgba(0,0,0,0.8), transparent)' }}>
                  <div className="bg-white rounded-4 p-3 shadow-sm border-top border-primary border-4">
                    <div className="d-flex justify-content-between align-items-center mb-3">
                      <span className="fw-bold text-dark fs-5">{overview.title}</span>
                      <span className="badge bg-success text-white rounded-pill px-3">Live</span>
                    </div>
                    <div className="d-flex justify-content-between">
                      <div>
                        <small className="text-muted d-block fw-semibold">{overview.leftLabel}</small>
                        <span className="fs-4 fw-bold text-primary">{overview.leftValue}</span>
                      </div>
                      <div className="text-end">
                        <small className="text-muted d-block fw-semibold">{overview.rightLabel}</small>
                        <span className="fs-4 fw-bold text-primary">{overview.rightValue}</span>
                      </div>
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      </section>

      <section className="row g-4 mb-5">
        {features.map((feature) => (
          <div key={feature.title} className="col-md-4">
            <div className="card border-0 shadow-sm rounded-4 h-100 p-4 bg-white" style={{ transition: 'transform 0.3s ease' }}>
              <div className="home-feature-icon mb-3">{feature.icon}</div>
              <h4 className="fw-bold text-dark">{feature.title}</h4>
              <p className="text-secondary mb-0 fs-6">{feature.desc}</p>
            </div>
          </div>
        ))}
      </section>

      <section className="rounded-4 p-5 text-center text-white shadow-lg mb-4" style={{ background: 'linear-gradient(135deg, #2563eb, #0f172a)' }}>
        <span className="badge bg-white bg-opacity-25 text-white rounded-pill px-3 py-2 mb-3 shadow-sm border border-light">READY TO EXPLORE</span>
        <h2 className="display-6 fw-bold mb-4">Launch your smart campus experience today.</h2>
        <Link to={isAuthenticated ? dashboardPath : '/login'} className="btn btn-light btn-lg text-primary rounded-pill px-5 fw-bold shadow">
          {isAuthenticated ? 'Open Dashboard' : 'Get Started Now'}
        </Link>
      </section>
    </Layout>
  );
}

export default HomePage;