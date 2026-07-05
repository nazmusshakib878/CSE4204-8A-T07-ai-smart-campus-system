import Layout from '../components/Layout';

function FunctionsPage() {
  const cards = [
    { title: 'Attendance Records', desc: 'Monitor daily presence and class participation.', badge: 'Live' },
    { title: 'Gradebook', desc: 'View assignments, marks, and semester progress.', badge: 'Updated' },
    { title: 'Campus Notices', desc: 'Read announcements and important department updates.', badge: 'New' },
    { title: 'Learning Resources', desc: 'Access study materials and helpful references.', badge: 'Popular' }
  ];

  return (
    <Layout title="Main Functional Pages" subtitle="Explore the platform’s core academic and administrative tools.">
      <div className="row g-4">
        {cards.map((card) => (
          <div key={card.title} className="col-md-6">
            <div className="card border-0 shadow-sm rounded-4 p-4 h-100">
              <div className="section-card-header d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h5 className="fw-bold text-dark mb-0">{card.title}</h5>
                <span className="badge bg-primary-subtle text-primary">{card.badge}</span>
              </div>
              <p className="text-secondary mb-0">{card.desc}</p>
            </div>
          </div>
        ))}
      </div>
    </Layout>
  );
}

export default FunctionsPage;
