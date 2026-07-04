import React from 'react';
import 'bootstrap/dist/css/bootstrap.min.css';

function App() {
  const features = [
    { title: "Real-time Analytics", desc: "Live attendance, marks, and CGPA tracking with semester-over-semester trend analysis.", icon: "📊" },
    { title: "AI Chat Assistant", desc: "Gemini-powered study assistant available 24/7 for academic guidance and exam preparation.", icon: "🤖" },
    { title: "Smart Recommendations", desc: "AI-curated course paths personalized from your academic performance profile.", icon: "💡" },
    { title: "Risk Early Warnings", desc: "Automated alerts for students with declining attendance or grades before it becomes critical.", icon: "⚠️" },
    { title: "Secure Role Access", desc: "Strict role-based access control for students, faculty, and administrators.", icon: "🛡️" },
    { title: "Campus Notices", desc: "Centralized notice management with targeted audience delivery across all departments.", icon: "🔔" }
  ];

  return (
    <div style={{ backgroundColor: '#F8FAFC', minHeight: '100vh', fontFamily: 'sans-serif' }}>
      
      {/* Navbar */}
      <nav className="navbar navbar-light bg-white shadow-sm p-3">
        <div className="container d-flex justify-content-between align-items-center">
          <h4 className="fw-bold text-dark mb-0">🎓 NUBTKCampus</h4>
          <div className="d-flex gap-4 align-items-center">
            <a href="#" className="text-decoration-none text-secondary d-none d-md-block">Blueprint</a>
            <a href="#" className="text-decoration-none text-secondary">Sign In</a>
            <button className="btn text-white rounded-pill px-4" style={{ backgroundColor: '#1E3A8A' }}>Get Started</button>
          </div>
        </div>
      </nav>

      {/* Hero Section */}
      <section className="container mt-5 pt-4 pb-5">
        <div className="row align-items-center">
          <div className="col-md-6">
            <span className="badge bg-light text-success mb-3 p-2 rounded-pill border">✨ AI-Powered Academic Platform</span>
            <h1 className="fw-bold display-5 text-dark">Smarter Campus.<br/><span style={{ color: '#1E3A8A' }}>Better Outcomes.</span></h1>
            <p className="text-secondary mt-3">
              NUBTK Campus unifies attendance tracking, academic analytics, and AI-guided learning — empowering students, faculty, and administrators.
            </p>
            <div className="mt-4 d-flex gap-3">
              <button className="btn text-white rounded-pill px-4 py-2" style={{ backgroundColor: '#1E3A8A' }}>Create Account →</button>
              <button className="btn btn-light border rounded-pill px-4 py-2">Sign In</button>
            </div>
            
            {/* Stats */}
            <div className="d-flex gap-4 gap-md-5 mt-5">
              <div><h4 className="fw-bold mb-0">8,400+</h4><small className="text-secondary">Students</small></div>
              <div><h4 className="fw-bold mb-0">340+</h4><small className="text-secondary">Faculty</small></div>
              <div><h4 className="fw-bold mb-0">99.2%</h4><small className="text-secondary">Uptime</small></div>
            </div>
          </div>
          
          {/* Hero Image */}
          <div className="col-md-6 mt-5 mt-md-0 position-relative">
            <img src="https://images.unsplash.com/photo-1541339907198-e08756dedf3f?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" alt="Campus" className="img-fluid rounded-4 shadow" />
            <div className="position-absolute bottom-0 start-0 m-4 bg-white p-3 rounded-3 shadow">
              <small className="text-secondary d-block">Avg. CGPA</small>
              <h5 className="fw-bold mb-0">3.74</h5>
              <small className="text-success">▲ +0.12 this semester</small>
            </div>
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section className="container py-5 mt-5 text-center">
        <p className="text-success fw-bold mb-1" style={{ letterSpacing: '2px', fontSize: '12px' }}>PLATFORM FEATURES</p>
        <h2 className="fw-bold mb-5">Everything in one platform</h2>
        
        <div className="row g-4 text-start">
          {features.map((feature, index) => (
            <div className="col-md-4" key={index}>
              <div className="p-4 bg-white rounded-4 shadow-sm h-100 border">
                <div className="fs-4 mb-3">{feature.icon}</div>
                <h5 className="fw-bold">{feature.title}</h5>
                <p className="text-secondary mb-0" style={{ fontSize: '14px' }}>{feature.desc}</p>
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* CTA / Footer Section */}
      <section className="mt-5 py-5 text-center text-white" style={{ backgroundColor: '#1E3A8A' }}>
        <div className="container pb-4 pt-4">
          <p className="text-uppercase mb-2" style={{ letterSpacing: '2px', fontSize: '12px', color: '#93C5FD' }}>JOIN NUBTK CAMPUS</p>
          <h2 className="fw-bold mb-3">Ready to transform your academic journey?</h2>
          <p className="mb-4 text-light" style={{ maxWidth: '600px', margin: '0 auto' }}>
            Join 8,400+ students and 340 faculty already using NUBTK Campus to track, learn, and grow.
          </p>
          <button className="btn rounded-pill px-4 py-2 fw-bold shadow-sm" style={{ backgroundColor: '#00D8A6', color: '#064E3B' }}>
            Register Now →
          </button>
        </div>
      </section>

      {/* Bottom Footer */}
      <footer className="bg-white py-4 border-top text-center text-md-start">
        <div className="container d-flex flex-column flex-md-row justify-content-between align-items-center">
          <small className="text-secondary mb-2 mb-md-0">
            © 2026 Northern University of Business and Technology | Khulna - Academic Management System v3.0
          </small>
          <a href="#" className="text-decoration-none text-secondary small">
            📄 View System Blueprint (PDF)
          </a>
        </div>
      </footer>

    </div>
  );
}

export default App;