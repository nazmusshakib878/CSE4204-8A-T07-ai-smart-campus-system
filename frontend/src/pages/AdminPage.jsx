import Layout from '../components/Layout';

function AdminPage() {
  const adminCards = [
    { title: 'User Management', desc: 'Manage student, faculty, and admin accounts.', stat: 'Accounts', tone: 'blue' },
    { title: 'System Reports', desc: 'View campus analytics and performance summaries.', stat: 'Reports', tone: 'green' },
    { title: 'Notice Control', desc: 'Publish announcements across departments.', stat: 'Notices', tone: 'amber' },
    { title: 'Role Permissions', desc: 'Configure access and security settings.', stat: 'Access', tone: 'violet' },
  ];

  return (
    <Layout title="Admin Panel" subtitle="Manage operations, user access, and campus-level settings.">
      <div className="admin-banner mb-4">
        <div>
          <span className="eyebrow-label">Administration</span>
          <h3>Control center for campus operations</h3>
          <p>Review user access, reports, notices, and system permissions from one focused workspace.</p>
        </div>
      </div>

      <div className="row g-4">
        {adminCards.map((card) => (
          <div key={card.title} className="col-md-6 col-xl-3">
            <div className={`admin-card admin-card-${card.tone} h-100`}>
              <span>{card.stat}</span>
              <h5>{card.title}</h5>
              <p>{card.desc}</p>
              <button type="button" className="btn btn-light w-100">Manage</button>
            </div>
          </div>
        ))}
      </div>
    </Layout>
  );
}

export default AdminPage;