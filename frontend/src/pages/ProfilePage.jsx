import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import Layout from '../components/Layout';
import { useAuth } from '../auth/auth-context';

const getProfilePhotoKey = (profile) => {
  const owner = profile?.id || profile?.email || profile?.student_id || profile?.varsity_id || profile?.university_id;
  return owner ? `profile_photo_${owner}` : '';
};

function ProfilePage() {
  const { user: profile } = useAuth();
  const [profilePhoto, setProfilePhoto] = useState('');
  const [photoError, setPhotoError] = useState('');
  const profilePhotoKey = useMemo(() => getProfilePhotoKey(profile), [profile]);
  const initials = profile?.name?.slice(0, 2).toUpperCase() || 'US';
  const role = profile?.role || 'Student';
  const approvalStatus = profile?.approval_status || 'Pending';
  const department = profile?.department || profile?.program || 'CSE Department';
  const studentId = profile?.student_id || profile?.varsity_id || profile?.university_id || 'Not added';
  const phone = profile?.phone || profile?.phone_number || 'Not added';
  const session = profile?.session || profile?.batch || 'Not added';

  useEffect(() => {
    setProfilePhoto(profilePhotoKey ? localStorage.getItem(profilePhotoKey) || '' : '');
  }, [profilePhotoKey]);

  const updateProfilePhoto = (photo) => {
    if (!profilePhotoKey) return;

    if (photo) {
      localStorage.setItem(profilePhotoKey, photo);
    } else {
      localStorage.removeItem(profilePhotoKey);
    }

    setProfilePhoto(photo);
    window.dispatchEvent(new CustomEvent('profile-photo-updated', {
      detail: { key: profilePhotoKey, photo },
    }));
  };

  const handlePhotoChange = (event) => {
    const file = event.target.files?.[0];
    setPhotoError('');

    if (!file) return;

    if (!profilePhotoKey) {
      setPhotoError('Could not identify this account. Please login again.');
      event.target.value = '';
      return;
    }

    if (!file.type.startsWith('image/')) {
      setPhotoError('Please select an image file.');
      event.target.value = '';
      return;
    }

    if (file.size > 2 * 1024 * 1024) {
      setPhotoError('Image must be 2 MB or smaller.');
      event.target.value = '';
      return;
    }

    const reader = new FileReader();
    reader.onload = () => updateProfilePhoto(reader.result);
    reader.onerror = () => setPhotoError('Could not upload this image. Please try another one.');
    reader.readAsDataURL(file);
  };

  const profileStats = [
    { label: 'Role', value: role },
    { label: 'Status', value: approvalStatus },
    { label: 'Department', value: department },
  ];

  const profileRows = [
    { label: 'Full Name', value: profile?.name || 'User' },
    { label: 'Email', value: profile?.email || 'N/A' },
    { label: 'Student / Staff ID', value: studentId },
    { label: 'Phone Number', value: phone },
    { label: 'Session / Batch', value: session },
    { label: 'Account Status', value: approvalStatus },
  ];

  return (
    <Layout title="My Profile" subtitle="Keep your campus identity clear, complete, and ready to use.">
      <div className="profile-hero mb-4">
        <div className="profile-hero-main">
          <div className="profile-photo-preview profile-photo-preview-lg">
            {profilePhoto ? (
              <img src={profilePhoto} alt={`${profile?.name || 'User'} profile`} />
            ) : (
              <span>{initials}</span>
            )}
          </div>
          <div className="min-w-0">
            <span className="eyebrow-label">Campus account</span>
            <h3>{profile?.name || 'User'}</h3>
            <p>{department} | {role}</p>
            <div className="profile-hero-actions">
              <label className="btn btn-light rounded-pill px-4" htmlFor="profile-photo-upload">
                Upload Photo
              </label>
              <input
                id="profile-photo-upload"
                className="visually-hidden"
                type="file"
                accept="image/*"
                onChange={handlePhotoChange}
              />
              {profilePhoto && (
                <button
                  type="button"
                  className="btn btn-outline-light rounded-pill px-4"
                  onClick={() => updateProfilePhoto('')}
                >
                  Remove
                </button>
              )}
            </div>
            {photoError && <small className="profile-photo-error">{photoError}</small>}
          </div>
        </div>
        <div className="profile-hero-status">
          <span>Approval</span>
          <strong className="text-capitalize">{approvalStatus}</strong>
        </div>
      </div>

      <div className="row g-4 mb-4">
        {profileStats.map((item) => (
          <div key={item.label} className="col-md-4">
            <div className="metric-card h-100">
              <span>{item.label}</span>
              <strong className="text-capitalize">{item.value}</strong>
            </div>
          </div>
        ))}
      </div>

      <div className="row g-4">
        <div className="col-xl-8">
          <div className="card border-0 shadow-sm rounded-4 p-4 h-100 profile-detail-card">
            <div className="section-card-header d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
              <div>
                <span className="eyebrow-label text-primary">Information</span>
                <h5 className="fw-bold text-dark mb-0">Profile details</h5>
              </div>
              <span className="badge bg-success-subtle text-success rounded-pill px-3 py-2">Verified account</span>
            </div>
            <div className="row g-3">
              {profileRows.map((row) => (
                <div key={row.label} className="col-md-6">
                  <div className="profile-info-tile">
                    <span>{row.label}</span>
                    <strong>{row.value}</strong>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        <div className="col-xl-4">
          <div className="d-grid gap-4">
            <div className="card border-0 shadow-sm rounded-4 p-4 profile-side-card">
              <span className="eyebrow-label text-primary">Quick actions</span>
              <h5 className="fw-bold text-dark mb-3">Campus shortcuts</h5>
              <div className="d-grid gap-2">
                <Link to="/dashboard" className="btn btn-outline-secondary text-start">Open dashboard</Link>
                <Link to="/functions" className="btn btn-outline-secondary text-start">Campus tools</Link>
                <Link to="/ai-assistant" className="btn btn-primary text-start">Ask AI Assistant</Link>
              </div>
            </div>

            <div className="card border-0 shadow-sm rounded-4 p-4 profile-side-card">
              <span className="eyebrow-label text-primary">Account health</span>
              <h5 className="fw-bold text-dark mb-3">Completion checklist</h5>
              <div className="profile-check-list">
                <span className={profilePhoto ? 'is-done' : ''}>Profile photo</span>
                <span className={profile?.email ? 'is-done' : ''}>Email address</span>
                <span className={approvalStatus === 'approved' ? 'is-done' : ''}>Approval status</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Layout>
  );
}

export default ProfilePage;