import { useEffect, useMemo, useState } from 'react';
import Layout from '../components/Layout';
import { EmptyState, LoadingState, StatusAlert } from '../components/Feedback';
import { createAdmin, getPendingUsers, updateUserApproval } from '../services/api';
import { validateCreateAdminForm } from '../utils/validation';

const emptyAdminForm = {
  name: '',
  email: '',
  phone: '',
  password: '',
  admin_id: '',
};

const formatRole = (role) => (role ? `${role.charAt(0).toUpperCase()}${role.slice(1)}` : 'User');
const formatDate = (date) => (date ? date.slice(0, 10) : 'Pending');
const universityId = (user) => user.student_id || user.faculty_id || user.admin_id || 'Not assigned';

function ManageUsersPage() {
  const [filter, setFilter] = useState('All');
  const [users, setUsers] = useState([]);
  const [loadingUsers, setLoadingUsers] = useState(true);
  const [updatingUserId, setUpdatingUserId] = useState(null);
  const [feedback, setFeedback] = useState(null);
  const [adminForm, setAdminForm] = useState(emptyAdminForm);
  const [adminErrors, setAdminErrors] = useState({});
  const [creatingAdmin, setCreatingAdmin] = useState(false);

  useEffect(() => {
    let active = true;

    getPendingUsers()
      .then((response) => {
        if (active) setUsers(response.data.data || []);
      })
      .catch((error) => {
        if (active) setFeedback({ variant: 'danger', message: error.message || 'Pending users could not be loaded.' });
      })
      .finally(() => {
        if (active) setLoadingUsers(false);
      });

    return () => {
      active = false;
    };
  }, []);

  const counts = useMemo(() => ({
    All: users.length,
    Student: users.filter((user) => user.role === 'student').length,
    Faculty: users.filter((user) => user.role === 'faculty').length,
  }), [users]);

  const visibleUsers = filter === 'All'
    ? users
    : users.filter((user) => formatRole(user.role) === filter);

  const handleDecision = async (user, decision) => {
    setUpdatingUserId(user.id);
    setFeedback(null);

    try {
      await updateUserApproval(user.id, decision);
      setUsers((currentUsers) => currentUsers.filter((item) => item.id !== user.id));
      setFeedback({
        variant: decision === 'approved' ? 'success' : 'warning',
        message: `${universityId(user)} (${user.name}) was ${decision}.`,
      });
    } catch (error) {
      setFeedback({ variant: 'danger', message: error.message || 'User approval status could not be updated.' });
    } finally {
      setUpdatingUserId(null);
    }
  };

  const handleAdminChange = (event) => {
    const { name, value } = event.target;
    setAdminForm((currentForm) => ({ ...currentForm, [name]: value }));
    setAdminErrors((currentErrors) => {
      if (!currentErrors[name]) return currentErrors;
      const nextErrors = { ...currentErrors };
      delete nextErrors[name];
      return nextErrors;
    });
  };

  const handleCreateAdmin = async (event) => {
    event.preventDefault();
    const payload = {
      ...adminForm,
      name: adminForm.name.trim(),
      email: adminForm.email.trim().toLowerCase(),
      phone: adminForm.phone.trim(),
      admin_id: adminForm.admin_id.trim().toUpperCase(),
    };
    const validationErrors = validateCreateAdminForm(payload);

    if (Object.keys(validationErrors).length > 0) {
      setAdminErrors(validationErrors);
      setFeedback({ variant: 'danger', message: 'Please correct the highlighted admin fields.' });
      return;
    }

    setCreatingAdmin(true);
    setAdminErrors({});
    setFeedback(null);

    try {
      await createAdmin(payload);
      setAdminForm(emptyAdminForm);
      setFeedback({ variant: 'success', message: 'Admin account created successfully.' });
    } catch (error) {
      if (Object.keys(error.fields || {}).length > 0) {
        setAdminErrors(error.fields);
      }
      setFeedback({ variant: 'danger', message: error.message || 'Admin account could not be created.' });
    } finally {
      setCreatingAdmin(false);
    }
  };

  return (
    <Layout title="Manage Users" subtitle="Approve or reject pending account registration requests">
      {feedback && (
        <StatusAlert
          variant={feedback.variant}
          message={feedback.message}
          onDismiss={() => setFeedback(null)}
        />
      )}

      <form className="faculty-panel mb-4" onSubmit={handleCreateAdmin} noValidate>
        <div className="section-card-header mb-3">
          <span className="eyebrow-label">Admin access</span>
          <h5 className="fw-bold text-dark mb-0">Create Admin</h5>
        </div>
        <div className="row g-3">
          <div className="col-md-6 col-xl-3">
            <label className="form-label fw-semibold" htmlFor="admin-name">Name</label>
            <input id="admin-name" name="name" className={`form-control${adminErrors.name ? ' is-invalid' : ''}`} value={adminForm.name} onChange={handleAdminChange} required />
            {adminErrors.name && <div className="invalid-feedback">{adminErrors.name}</div>}
          </div>
          <div className="col-md-6 col-xl-3">
            <label className="form-label fw-semibold" htmlFor="admin-email">Email</label>
            <input id="admin-email" name="email" type="email" className={`form-control${adminErrors.email ? ' is-invalid' : ''}`} value={adminForm.email} onChange={handleAdminChange} required />
            {adminErrors.email && <div className="invalid-feedback">{adminErrors.email}</div>}
          </div>
          <div className="col-md-6 col-xl-2">
            <label className="form-label fw-semibold" htmlFor="admin-phone">Phone</label>
            <input id="admin-phone" name="phone" type="tel" className={`form-control${adminErrors.phone ? ' is-invalid' : ''}`} value={adminForm.phone} onChange={handleAdminChange} placeholder="01712345678" required />
            {adminErrors.phone && <div className="invalid-feedback">{adminErrors.phone}</div>}
          </div>
          <div className="col-md-6 col-xl-2">
            <label className="form-label fw-semibold" htmlFor="admin-id">Admin ID</label>
            <input id="admin-id" name="admin_id" className={`form-control${adminErrors.admin_id ? ' is-invalid' : ''}`} value={adminForm.admin_id} onChange={handleAdminChange} placeholder="ADM-045" required />
            {adminErrors.admin_id && <div className="invalid-feedback">{adminErrors.admin_id}</div>}
          </div>
          <div className="col-md-6 col-xl-2">
            <label className="form-label fw-semibold" htmlFor="admin-password">Password</label>
            <input id="admin-password" name="password" type="password" className={`form-control${adminErrors.password ? ' is-invalid' : ''}`} value={adminForm.password} onChange={handleAdminChange} required />
            {adminErrors.password && <div className="invalid-feedback">{adminErrors.password}</div>}
          </div>
          <div className="col-md-6 col-xl-2 d-flex align-items-end">
            <button type="submit" className="btn btn-primary w-100" disabled={creatingAdmin} aria-busy={creatingAdmin}>
              {creatingAdmin ? 'Creating...' : 'Create Admin'}
            </button>
          </div>
        </div>
      </form>

      <div className="admin-filter-tabs mb-4">
        {['All', 'Student', 'Faculty'].map((item) => (
          <button key={item} type="button" className={filter === item ? 'is-active' : ''} onClick={() => setFilter(item)}>
            {item} ({counts[item]})
          </button>
        ))}
      </div>

      {loadingUsers ? (
        <LoadingState message="Loading pending users..." />
      ) : visibleUsers.length > 0 ? (
        <section className="faculty-panel p-0 overflow-hidden">
          <div className="table-responsive">
            <table className="table faculty-table admin-user-table align-middle mb-0">
              <thead>
                <tr>
                  <th>University ID</th><th>Name</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Role</th>
                  <th>Department</th>
                  <th>Applied On</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {visibleUsers.map((user) => (
                  <tr key={user.id}><td><strong>{universityId(user)}</strong></td>
                    <td>
                      <div className="admin-user-cell">
                        <span>{user.name.charAt(0)}</span>
                        <strong>{user.name}</strong>
                      </div>
                    </td>
                    <td>{user.email}</td>
                    <td>{user.phone || '-'}</td>
                    <td><span className={`course-pill ${user.role === 'faculty' ? 'course-pill-violet' : 'course-pill-primary'}`}>{formatRole(user.role)}</span></td>
                    <td>{user.department}</td>
                    <td>{formatDate(user.created_at)}</td>
                    <td>
                      <div className="d-flex flex-wrap gap-2">
                        <button type="button" className="btn btn-sm btn-success" onClick={() => handleDecision(user, 'approved')} disabled={updatingUserId === user.id}>Approve</button>
                        <button type="button" className="btn btn-sm btn-outline-danger" onClick={() => handleDecision(user, 'rejected')} disabled={updatingUserId === user.id}>Reject</button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </section>
      ) : (
        <EmptyState title="No pending users" message="Student and faculty registration requests will appear here." />
      )}
    </Layout>
  );
}

export default ManageUsersPage;