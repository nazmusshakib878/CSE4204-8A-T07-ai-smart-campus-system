import Layout from '../components/Layout';

function AdminPage() {
  const adminCards = [
    { title: 'User Management', desc: 'Manage student, faculty, and admin accounts.' },
    { title: 'System Reports', desc: 'View campus analytics and performance summaries.' },
    { title: 'Notice Control', desc: 'Publish announcements across departments.' },
    { title: 'Role Permissions', desc: 'Configure access and security settings.' }
  ];

  return (
    <Layout title="Admin Panel" subtitle="Manage operations, user access, and campus-level settings.">
      <div className="row g-4">
        {adminCards.map((card) => (
          <div key={card.title} className="col-md-6">
            <div className="card border-0 shadow-sm rounded-4 p-4 h-100">
              <h5 className="fw-bold text-dark mb-2">{card.title}</h5>
              <p className="text-secondary mb-0">{card.desc}</p>
            </div>
          </div>
        ))}
      </div>
    </Layout>
  );
}

export default AdminPage;
