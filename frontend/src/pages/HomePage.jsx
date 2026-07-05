import { Link } from 'react-router-dom';
import Layout from '../components/Layout';
import campusImg from '../assets/ChatGPT Image Jul 4, 2026, 11_22_37 PM.png';

function HomePage() {
  const features = [
    { title: 'Attendance Tracking', desc: 'Stay updated with class participation and absence patterns.', icon: '📈' },
    { title: 'Academic Insights', desc: 'Review marks, GPA trends, and performance summaries clearly.', icon: '🎯' },
    { title: 'AI Study Support', desc: 'Get smart recommendations and academic guidance instantly.', icon: '🤖' }
  ];

  return (
    <Layout title="Welcome to NUBTK Campus" subtitle="A smart platform for students, faculty, and administrators.">
      <section className="home-hero row align-items-center g-5 mb-5">
        <div className="col-lg-7">
          <span className="badge rounded-pill bg-primary-subtle text-primary px-3 py-2 mb-3">AI-POWERED EDUCATION EXPERIENCE</span>
          <h1 className="display-5 fw-bold text-dark mb-3">Smarter campus operations from one unified platform.</h1>
          <p className="lead text-secondary mb-4">
            Manage learning, attendance, student progress, and communication in a modern experience tailored for universities.
          </p>
          <div className="hero-actions d-flex flex-wrap gap-3 mb-4">
            <Link to="/register" className="btn btn-primary rounded-pill px-4 py-2">Create Account</Link>
            <Link to="/dashboard" className="btn btn-outline-secondary rounded-pill px-4 py-2">View Dashboard</Link>
          </div>
          <div className="hero-stats d-flex flex-wrap gap-3">
            <div className="bg-white rounded-4 px-3 py-2 shadow-sm border">
              <div className="fw-bold text-dark">8,400+ Students</div>
              <small className="text-secondary">Active users</small>
            </div>
            <div className="bg-white rounded-4 px-3 py-2 shadow-sm border">
              <div className="fw-bold text-dark">24/7 AI Support</div>
              <small className="text-secondary">Always available</small>
            </div>
          </div>
        </div>

        <div className="col-lg-5">
          <div className="card border-0 shadow-lg rounded-4 overflow-hidden">
            <img src={campusImg} alt="Campus management preview" className="home-hero-image img-fluid w-100" style={{ height: 320, objectFit: 'cover' }} />
            <div className="card-body p-4">
              <div className="d-flex justify-content-between align-items-center mb-3">
                <div>
                  <p className="small text-muted mb-1">Today’s overview</p>
                  <h5 className="fw-bold mb-0">Academic performance</h5>
                </div>
                <span className="badge bg-success-subtle text-success">Live</span>
              </div>
              <div className="row g-3">
                <div className="col-6">
                  <div className="rounded-3 bg-light p-3">
                    <div className="small text-muted">Avg. CGPA</div>
                    <div className="fw-bold fs-5 text-dark">3.74</div>
                  </div>
                </div>
                <div className="col-6">
                  <div className="rounded-3 bg-light p-3">
                    <div className="small text-muted">Attendance</div>
                    <div className="fw-bold fs-5 text-dark">92.4%</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="row g-4 mb-5">
        {features.map((feature) => (
          <div key={feature.title} className="col-md-6 col-xl-4">
            <div className="card border-0 shadow-sm rounded-4 h-100 p-4">
              <div className="fs-3 mb-3">{feature.icon}</div>
              <h5 className="fw-bold text-dark">{feature.title}</h5>
              <p className="text-secondary mb-0">{feature.desc}</p>
            </div>
          </div>
        ))}
      </section>

      <section className="home-cta rounded-4 p-5 text-white" style={{ background: 'linear-gradient(135deg, #1d4ed8, #0f172a)' }}>
        <div className="text-center">
          <p className="text-uppercase small mb-2" style={{ letterSpacing: '2px', opacity: 0.8 }}>READY TO EXPLORE</p>
          <h2 className="fw-bold mb-3">Launch your smart campus experience today.</h2>
          <p className="mb-4 mx-auto text-light" style={{ maxWidth: 680 }}>
            Register, access your dashboard, explore academic tools, and interact with the AI assistant from one portal.
          </p>
          <Link to="/login" className="btn btn-light text-primary rounded-pill px-4 py-2 fw-semibold">Get Started</Link>
        </div>
      </section>
    </Layout>
  );
}

export default HomePage;
