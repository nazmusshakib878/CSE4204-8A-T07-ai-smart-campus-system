import Layout from '../components/Layout';

function FunctionsPage() {
  const cards = [
    { title: 'Attendance Records', desc: 'Monitor daily presence, class participation, and student consistency.', badge: 'Live', icon: 'AR', tone: 'blue' },
    { title: 'Gradebook', desc: 'Review marks, assignments, exam progress, and semester performance.', badge: 'Updated', icon: 'GB', tone: 'green' },
    { title: 'Campus Notices', desc: 'Read department announcements, deadlines, and official updates.', badge: 'New', icon: 'CN', tone: 'amber' },
    { title: 'Learning Resources', desc: 'Access notes, references, study materials, and helpful links.', badge: 'Popular', icon: 'LR', tone: 'violet' },
  ];

  return (
    <Layout title="Campus Tools" subtitle="Explore the platform's core academic and administrative tools.">
      <div className="tool-banner mb-4">
        <div>
          <span className="eyebrow-label">Workspace</span>
          <h3>Everything for daily campus work</h3>
          <p>Quickly move between records, resources, notices, and progress tracking from one clean area.</p>
        </div>
        <span className="tool-banner-count">{cards.length} tools</span>
      </div>

      <div className="row g-4">
        {cards.map((card) => (
          <div key={card.title} className="col-md-6 col-xl-3">
            <div className={`tool-card tool-card-${card.tone} h-100`}>
              <div className="tool-icon">{card.icon}</div>
              <div className="section-card-header d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h5 className="fw-bold text-dark mb-0">{card.title}</h5>
                <span className="badge rounded-pill bg-white text-primary">{card.badge}</span>
              </div>
              <p className="text-secondary mb-4">{card.desc}</p>
              <button type="button" className="btn btn-outline-secondary w-100">Open tool</button>
            </div>
          </div>
        ))}
      </div>
    </Layout>
  );
}

export default FunctionsPage;