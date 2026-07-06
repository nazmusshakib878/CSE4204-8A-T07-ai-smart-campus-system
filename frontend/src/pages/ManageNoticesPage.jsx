import { useCallback, useEffect, useState } from 'react';
import Layout from '../components/Layout';
import { ConfirmDialog, EmptyState, LoadingState, ModalDialog, StatusAlert } from '../components/Feedback';
import { createNotice, deleteNotice, getDepartments, getNotices } from '../services/api';
import { useAuth } from '../auth/auth-context';

const emptyForm = {
  title: '',
  description: '',
  category: 'Academic',
  audience: 'All',
  target_department: '',
  target_role: 'All',
  target_semester: '',
  attachment: null,
};

const normalizeNotice = (notice) => ({
  ...notice,
  date: (notice.publish_date || notice.created_at || '').slice(0, 10),
  tags: [notice.category || 'Academic', notice.audience || 'All', notice.target_department, notice.target_role, notice.target_semester]
    .filter(Boolean),
});

function ManageNoticesPage() {
  const { user } = useAuth();
  const isAdmin = user?.role === 'admin';
  const [notices, setNotices] = useState([]);
  const [departments, setDepartments] = useState([]);
  const [showForm, setShowForm] = useState(false);
  const [feedback, setFeedback] = useState(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [selectedNotice, setSelectedNotice] = useState(null);
  const [noticeToDelete, setNoticeToDelete] = useState(null);
  const [form, setForm] = useState(emptyForm);

  const fetchNotices = useCallback(async () => {
    setLoading(true);
    setError('');

    try {
      const [noticeResponse, departmentResponse] = await Promise.all([
        getNotices(),
        getDepartments(),
      ]);
      setNotices((noticeResponse.data.data || []).map(normalizeNotice));
      setDepartments(departmentResponse.data.data || []);
    } catch (requestError) {
      setError(requestError.message || 'Notices could not be loaded.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchNotices();
  }, [fetchNotices]);

  const handleChange = (event) => {
    const { name, value, files } = event.target;
    setForm((current) => ({ ...current, [name]: files ? files[0] || null : value }));
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    setSaving(true);
    setFeedback(null);

    const payload = {
      title: form.title.trim(),
      description: form.description.trim(),
      category: form.category,
      audience: isAdmin ? form.audience : 'Department',
      target_department: isAdmin ? form.target_department : (user?.department || form.target_department),
      target_role: isAdmin ? form.target_role : 'Students',
      target_semester: form.target_semester.trim(),
    };

    if (payload.audience !== 'Department') {
      payload.target_department = '';
      payload.target_role = payload.audience === 'Students' ? 'Students' : payload.audience === 'Faculty' ? 'Faculty' : 'All';
      payload.target_semester = '';
    }

    const requestPayload = new FormData();
    Object.entries(payload).forEach(([key, value]) => requestPayload.append(key, value || ''));
    if (form.attachment) requestPayload.append('attachment', form.attachment);

    try {
      const response = await createNotice(requestPayload);
      setNotices((currentNotices) => [normalizeNotice(response.data.data), ...currentNotices]);
      setForm(emptyForm);
      setShowForm(false);
      setFeedback({ variant: 'success', message: 'Notice published successfully.' });
    } catch (requestError) {
      setFeedback({ variant: 'danger', message: requestError.message || 'The notice could not be published.' });
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async () => {
    if (!noticeToDelete) return;

    setDeleting(true);
    setFeedback(null);

    try {
      await deleteNotice(noticeToDelete.id);
      setNotices((currentNotices) => currentNotices.filter((item) => item.id !== noticeToDelete.id));
      setFeedback({ variant: 'warning', message: `${noticeToDelete.title} was removed.` });
      setSelectedNotice((current) => (current?.id === noticeToDelete.id ? null : current));
      setNoticeToDelete(null);
    } catch (requestError) {
      setFeedback({ variant: 'danger', message: requestError.message || 'The notice could not be deleted.' });
      setNoticeToDelete(null);
    } finally {
      setDeleting(false);
    }
  };

  const requestDelete = (notice) => {
    setSelectedNotice(null);
    setNoticeToDelete(notice);
  };

  return (
    <>
      <Layout title="Manage Notices" subtitle={isAdmin ? 'Create campus-wide and department notices' : 'Send notices to your students'}>
        {feedback && (
          <StatusAlert
            variant={feedback.variant}
            message={feedback.message}
            onDismiss={() => setFeedback(null)}
          />
        )}
        {error && (
          <StatusAlert
            variant="danger"
            message={error}
            actionLabel="Try again"
            onAction={fetchNotices}
            onDismiss={() => setError('')}
          />
        )}

        <div className="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
          <span className="admin-total-count">{notices.length} notices total</span>
          <button type="button" className="btn btn-primary px-4" onClick={() => setShowForm((open) => !open)}>
            {showForm ? 'Cancel' : '+ New Notice'}
          </button>
        </div>

        {showForm && (
          <form className="faculty-panel mb-4" onSubmit={handleSubmit}>
            <div className="row g-3 align-items-end">
              <div className="col-lg-6">
                <label className="form-label fw-semibold" htmlFor="notice-title">Notice title</label>
                <input
                  id="notice-title"
                  name="title"
                  className="form-control"
                  maxLength={255}
                  value={form.title}
                  onChange={handleChange}
                  required
                />
              </div>
              <div className="col-md-6 col-lg-3">
                <label className="form-label fw-semibold" htmlFor="notice-category">Category</label>
                <select id="notice-category" name="category" className="form-select" value={form.category} onChange={handleChange}>
                  <option>Academic</option>
                  <option>Academic Risk</option>
                  <option>Exam</option>
                  <option>Holiday</option>
                  <option>Meeting</option>
                  <option>Facility</option>
                  <option>Payment</option>
                </select>
              </div>
              <div className="col-md-6 col-lg-3">
                <label className="form-label fw-semibold" htmlFor="notice-audience">Audience</label>
                {isAdmin ? (
                  <select id="notice-audience" name="audience" className="form-select" value={form.audience} onChange={handleChange}>
                    <option value="All">Full varsity</option>
                    <option value="Students">All students</option>
                    <option value="Faculty">All faculty</option>
                    <option value="Department">Department</option>
                  </select>
                ) : (
                  <input id="notice-audience" className="form-control" value="My department students" disabled />
                )}
              </div>

              {(!isAdmin || form.audience === 'Department') && (
                <>
                  <div className="col-md-6 col-lg-4">
                    <label className="form-label fw-semibold" htmlFor="notice-department">Department</label>
                    <select
                      id="notice-department"
                      name="target_department"
                      className="form-select"
                      value={isAdmin ? form.target_department : (user?.department || form.target_department)}
                      onChange={handleChange}
                      disabled={!isAdmin && Boolean(user?.department)}
                      required
                    >
                      <option value="">Select department</option>
                      {departments.map((department) => (
                        <option key={department.id} value={department.name}>{department.name}</option>
                      ))}
                    </select>
                  </div>
                  <div className="col-md-6 col-lg-4">
                    <label className="form-label fw-semibold" htmlFor="notice-target-role">Send to</label>
                    <select
                      id="notice-target-role"
                      name="target_role"
                      className="form-select"
                      value={isAdmin ? form.target_role : 'Students'}
                      onChange={handleChange}
                      disabled={!isAdmin}
                    >
                      <option value="All">Faculty + students</option>
                      <option value="Students">Students only</option>
                      <option value="Faculty">Faculty only</option>
                    </select>
                  </div>
                  <div className="col-md-6 col-lg-4">
                    <label className="form-label fw-semibold" htmlFor="notice-semester">Semester</label>
                    <input
                      id="notice-semester"
                      name="target_semester"
                      className="form-control"
                      value={form.target_semester}
                      onChange={handleChange}
                      placeholder="Optional, e.g. 8"
                    />
                  </div>
                </>
              )}

              <div className="col-12">
                <label className="form-label fw-semibold" htmlFor="notice-description">Description</label>
                <textarea
                  id="notice-description"
                  name="description"
                  className="form-control"
                  rows="4"
                  maxLength={5000}
                  value={form.description}
                  onChange={handleChange}
                  required
                />
              </div>
              <div className="col-md-6">
                <label className="form-label fw-semibold" htmlFor="notice-attachment">Due list PDF</label>
                <input
                  id="notice-attachment"
                  name="attachment"
                  type="file"
                  accept="application/pdf,.pdf"
                  className="form-control"
                  onChange={handleChange}
                />
              </div>
              <div className="col-12 d-flex justify-content-end">
                <button type="submit" className="btn btn-primary px-4" disabled={saving} aria-busy={saving}>
                  {saving && <span className="spinner-border spinner-border-sm me-2" aria-hidden="true" />}
                  {saving ? 'Publishing...' : 'Publish'}
                </button>
              </div>
            </div>
          </form>
        )}

        {loading ? (
          <LoadingState message="Loading notices..." />
        ) : notices.length > 0 ? (
          <div className="admin-notice-list">
            {notices.map((notice) => (
              <article key={notice.id} className="admin-notice-card">
                <div className="min-w-0">
                  <div className="d-flex flex-wrap gap-2 mb-2">
                    {notice.tags.map((tag) => <span key={tag} className="course-pill course-pill-primary">{tag}</span>)}
                  </div>
                  <h4>{notice.title}</h4>
                  <small>{notice.date}</small>
                  {notice.attachment_url && (
                    <a className="d-inline-block mt-2 text-primary fw-semibold" href={notice.attachment_url} target="_blank" rel="noreferrer">
                      {notice.attachment_name || 'Open due list PDF'}
                    </a>
                  )}
                </div>
                <div className="admin-notice-actions">
                  <button type="button" className="btn btn-sm btn-link text-decoration-none" onClick={() => setSelectedNotice(notice)}>View</button>
                  {isAdmin && <button type="button" className="btn btn-sm btn-link text-danger text-decoration-none" onClick={() => requestDelete(notice)}>Delete</button>}
                </div>
              </article>
            ))}
          </div>
        ) : (
          <EmptyState title="No notices published" message="Use New Notice to publish the first announcement." />
        )}
      </Layout>

      <ModalDialog open={Boolean(selectedNotice)} title={selectedNotice?.title || 'Notice details'} onClose={() => setSelectedNotice(null)}>
        {selectedNotice && (
          <>
            <div className="d-flex flex-wrap gap-2 mb-3">
              {selectedNotice.tags.map((tag) => <span key={tag} className="course-pill course-pill-primary">{tag}</span>)}
            </div>
            <p className="text-secondary">{selectedNotice.description}</p>
            {selectedNotice.attachment_url && (
              <p>
                <a className="text-primary fw-semibold" href={selectedNotice.attachment_url} target="_blank" rel="noreferrer">
                  {selectedNotice.attachment_name || 'Open due list PDF'}
                </a>
              </p>
            )}
            <small className="text-secondary">
              Published on {selectedNotice.date || 'Not dated'}
              {selectedNotice.author?.name ? ` by ${selectedNotice.author.name}` : ''}
            </small>
            <div className="modal-footer px-0 pb-0 mt-4">
              {isAdmin && <button type="button" className="btn btn-outline-danger" onClick={() => requestDelete(selectedNotice)}>Delete</button>}
              <button type="button" className="btn btn-primary px-4" onClick={() => setSelectedNotice(null)}>Close</button>
            </div>
          </>
        )}
      </ModalDialog>

      <ConfirmDialog
        open={Boolean(noticeToDelete)}
        title="Delete this notice?"
        message={noticeToDelete ? `"${noticeToDelete.title}" will be removed from the notice list.` : ''}
        confirmLabel="Delete notice"
        loading={deleting}
        onConfirm={handleDelete}
        onCancel={() => {
          if (!deleting) setNoticeToDelete(null);
        }}
      />
    </>
  );
}

export default ManageNoticesPage;