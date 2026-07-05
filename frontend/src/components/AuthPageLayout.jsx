import campusImage from '../assets/ChatGPT Image Jul 4, 2026, 11_22_37 PM.png';

function AuthPageLayout({ eyebrow, title, subtitle, children }) {
  return (
    <div className="auth-page-grid">
      <section className="auth-story-panel">
        <img
          src={campusImage}
          alt="Northern University campus"
          className="auth-story-image"
        />
        <div className="auth-story-overlay" />
        <div className="auth-story-content">
          <span className="auth-story-badge">SMART CAMPUS PLATFORM</span>
          <h1>Smarter Campus.<br />Better Outcomes.</h1>
          <p>
            One connected space for learning, academic progress, campus tasks,
            and intelligent student support.
          </p>
          <div className="auth-story-stats">
            <div>
              <strong>8,400+</strong>
              <span>Students</span>
            </div>
            <div>
              <strong>340+</strong>
              <span>Faculty</span>
            </div>
            <div>
              <strong>92.2%</strong>
              <span>Attendance</span>
            </div>
          </div>
        </div>
      </section>

      <section className="auth-form-panel">
        <div className="auth-form-copy">
          <span className="auth-form-eyebrow">{eyebrow}</span>
          <h2>{title}</h2>
          <p>{subtitle}</p>
        </div>
        {children}
      </section>
    </div>
  );
}

export default AuthPageLayout;
