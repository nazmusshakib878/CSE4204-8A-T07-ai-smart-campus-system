import { useEffect, useMemo, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import Layout from '../components/Layout';
import { useAuth } from '../auth/auth-context';

const getProfilePhotoKey = (profile) => {
  const owner = profile?.id || profile?.email || profile?.student_id || profile?.varsity_id || profile?.university_id;
  return owner ? `profile_photo_${owner}` : '';
};

const iconPaths = {
  camera: 'M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h3l2-3h8l2 3h3a2 2 0 0 1 2 2v11ZM12 17a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z',
  eye: 'M1.5 12s4-7 10.5-7 10.5 7 10.5 7-4 7-10.5 7S1.5 12 1.5 12Zm10.5 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z',
  folder: 'M3 6a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6Z',
  trash: 'M4 7h16M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3',
};

function PhotoActionIcon({ name }) {
  return (
    <svg className="photo-action-icon" viewBox="0 0 24 24" aria-hidden="true">
      <path d={iconPaths[name]} />
    </svg>
  );
}

function ProfilePage() {
  const { user: profile } = useAuth();
  const [profilePhoto, setProfilePhoto] = useState('');
  const [photoError, setPhotoError] = useState('');
  const [photoMenuOpen, setPhotoMenuOpen] = useState(false);
  const [viewPhotoOpen, setViewPhotoOpen] = useState(false);
  const [cameraOpen, setCameraOpen] = useState(false);
  const [cameraReady, setCameraReady] = useState(false);
  const [cameraError, setCameraError] = useState('');
  const fileInputRef = useRef(null);
  const videoRef = useRef(null);
  const cameraStreamRef = useRef(null);
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

  useEffect(() => {
    if (!cameraOpen) return undefined;

    let cancelled = false;
    setCameraReady(false);
    setCameraError('');

    const startCamera = async () => {
      try {
        if (!navigator.mediaDevices?.getUserMedia) {
          throw new Error('Camera is not available in this browser.');
        }

        const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        if (cancelled) {
          stream.getTracks().forEach((track) => track.stop());
          return;
        }

        cameraStreamRef.current = stream;
        if (videoRef.current) {
          videoRef.current.srcObject = stream;
          await videoRef.current.play();
        }
        setCameraReady(true);
      } catch (error) {
        setCameraError(error.message || 'Could not open the camera.');
      }
    };

    startCamera();

    return () => {
      cancelled = true;
      cameraStreamRef.current?.getTracks().forEach((track) => track.stop());
      cameraStreamRef.current = null;
    };
  }, [cameraOpen]);

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
    setPhotoMenuOpen(false);
  };

  const openCamera = () => {
    setPhotoMenuOpen(false);
    setCameraOpen(true);
  };

  const capturePhoto = () => {
    if (!videoRef.current || !profilePhotoKey) return;

    const canvas = document.createElement('canvas');
    canvas.width = videoRef.current.videoWidth || 640;
    canvas.height = videoRef.current.videoHeight || 480;
    const context = canvas.getContext('2d');
    context.drawImage(videoRef.current, 0, 0, canvas.width, canvas.height);
    updateProfilePhoto(canvas.toDataURL('image/jpeg', 0.9));
    setCameraOpen(false);
  };

  const removePhoto = () => {
    updateProfilePhoto('');
    setPhotoMenuOpen(false);
    setViewPhotoOpen(false);
    setPhotoError('');
    if (fileInputRef.current) fileInputRef.current.value = '';
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
          <div className="profile-photo-control">
            <div className="profile-photo-preview profile-photo-preview-lg">
              {profilePhoto ? (
                <img src={profilePhoto} alt={`${profile?.name || 'User'} profile`} />
              ) : (
                <span>{initials}</span>
              )}
            </div>
            <button
              type="button"
              className="profile-photo-edit-button"
              aria-expanded={photoMenuOpen}
              onClick={() => setPhotoMenuOpen((open) => !open)}
            >
              <PhotoActionIcon name="camera" />
              <span>Edit</span>
            </button>
            {photoMenuOpen && (
              <div className="profile-photo-menu">
                <button
                  type="button"
                  disabled={!profilePhoto}
                  onClick={() => {
                    setPhotoMenuOpen(false);
                    setViewPhotoOpen(true);
                  }}
                >
                  <PhotoActionIcon name="eye" />
                  <span>View</span>
                </button>
                <button type="button" onClick={openCamera}>
                  <PhotoActionIcon name="camera" />
                  <span>Take</span>
                </button>
                <button type="button" onClick={() => fileInputRef.current?.click()}>
                  <PhotoActionIcon name="folder" />
                  <span>Upload</span>
                </button>
                <button type="button" disabled={!profilePhoto} onClick={removePhoto}>
                  <PhotoActionIcon name="trash" />
                  <span>Remove</span>
                </button>
              </div>
            )}
            <input
              ref={fileInputRef}
              id="profile-photo-upload"
              className="visually-hidden"
              type="file"
              accept="image/*"
              onChange={handlePhotoChange}
            />
          </div>
          <div className="min-w-0">
            <span className="eyebrow-label">Campus account</span>
            <h3>{profile?.name || 'User'}</h3>
            <p>{department} | {role}</p>
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

      {viewPhotoOpen && profilePhoto && (
        <div className="profile-photo-modal" role="dialog" aria-modal="true" aria-label="View profile photo">
          <div className="profile-photo-modal-card">
            <img src={profilePhoto} alt={`${profile?.name || 'User'} profile`} />
            <div className="profile-photo-modal-actions">
              <button type="button" className="btn btn-outline-secondary" onClick={() => setViewPhotoOpen(false)}>Close</button>
              <button type="button" className="btn btn-outline-danger" onClick={removePhoto}>Remove photo</button>
            </div>
          </div>
        </div>
      )}

      {cameraOpen && (
        <div className="profile-photo-modal" role="dialog" aria-modal="true" aria-label="Take profile photo">
          <div className="profile-photo-modal-card profile-camera-card">
            <div className="profile-camera-frame">
              {cameraError ? (
                <p>{cameraError}</p>
              ) : (
                <video ref={videoRef} playsInline muted />
              )}
            </div>
            <div className="profile-photo-modal-actions">
              <button type="button" className="btn btn-outline-secondary" onClick={() => setCameraOpen(false)}>Cancel</button>
              <button type="button" className="btn btn-primary" disabled={!cameraReady || Boolean(cameraError)} onClick={capturePhoto}>Use photo</button>
            </div>
          </div>
        </div>
      )}
    </Layout>
  );
}

export default ProfilePage;