import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Layout from '../components/Layout';
import { getProfile } from '../services/api';

function ProfilePage() {
  const navigate = useNavigate();
  const [profile, setProfile] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem('auth_token');
    if (!token) {
      navigate('/login');
      return;
    }

    getProfile()
      .then((response) => setProfile(response.data.user))
      .catch(() => navigate('/login'))
      .finally(() => setLoading(false));
  }, [navigate]);

  if (loading) {
    return <Layout title="User Profile" subtitle="Loading your profile..."><div className="text-secondary">Loading...</div></Layout>;
  }

  return (
    <Layout title="User Profile" subtitle="Manage your personal and academic profile information.">
      <div className="row g-4">
        <div className="col-lg-4">
          <div className="card border-0 shadow-sm rounded-4 p-4 text-center">
            <div className="rounded-circle mx-auto d-flex align-items-center justify-content-center text-white fw-bold fs-3 mb-3" style={{ width: 90, height: 90, background: 'linear-gradient(135deg, #2563eb, #0f172a)' }}>
              {profile?.name?.slice(0, 2).toUpperCase() || 'US'}
            </div>
            <h5 className="fw-bold text-dark">{profile?.name || 'User'}</h5>
            <p className="text-secondary mb-3">{profile?.role || 'User'} • {profile?.email || 'N/A'}</p>
          </div>
        </div>

        <div className="col-lg-8">
          <div className="card border-0 shadow-sm rounded-4 p-4">
            <h5 className="fw-bold text-dark mb-4">Profile details</h5>
            <div className="row g-3">
              <div className="col-md-6">
                <label className="form-label">Full Name</label>
                <input className="form-control" value={profile?.name || ''} readOnly />
              </div>
              <div className="col-md-6">
                <label className="form-label">Email</label>
                <input className="form-control" value={profile?.email || ''} readOnly />
              </div>
              <div className="col-md-6">
                <label className="form-label">Role</label>
                <input className="form-control" value={profile?.role || ''} readOnly />
              </div>
              <div className="col-md-6">
                <label className="form-label">Approval Status</label>
                <input className="form-control" value={profile?.approval_status || ''} readOnly />
              </div>
            </div>
          </div>
        </div>
      </div>
    </Layout>
  );
}

export default ProfilePage;
