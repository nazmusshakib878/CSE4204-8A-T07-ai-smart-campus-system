import { useCallback, useEffect, useState } from 'react';
import Layout from '../components/Layout';
import { ConfirmDialog, EmptyState, LoadingState, ModalDialog, StatusAlert } from '../components/Feedback';
import { createNotice, deleteNotice, getNotices } from '../services/api';

const normalizeNotice = (notice) => ({
  ...notice,
  date: (notice.publish_date || notice.created_at || '').slice(0, 10),
  tags: [notice.category || 'Academic', notice.audience || 'All'],
});

function ManageNoticesPage() {
  const [notices, setNotices] = useState([]);
  const [showForm, setShowForm] = useState(false);
  const [feedback, setFeedback] = useState(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [selectedNotice, setSelectedNotice] = useState(null);
  const [noticeToDelete, setNoticeToDelete] = useState(null);
  const [form, setForm] = useState({ title: '', description: '', category: 'Academic', audience: 'All' });

  const fetchNotices = useCallback(async () => {
    setLoading(true);
    setError('');

    try {
      const response = await getNotices();
      setNotices((response.data.data || []).map(normalizeNotice));
    } catch (requestError) {
      setError(requestError.message || 'Notices could not be loaded.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchNotices();
  }, [fetchNotices]);

  const handleSubmit = async (event) => {
    event.preventDefault();
    setSaving(true);
    setFeedback(null);

    try {
      const response = await createNotice({
        title: form.title.trim(),
        description: form.description.trim(),
        category: form.category,
        audience: form.audience,
      });
      setNotices((currentNotices) => [normalizeNotice(response.data.data), ...currentNotices]);
      setForm({ title: '', description: '', category: 'Academic', audience: 'All' });
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
      <Layout title="Manage Notices" subtitle="Create and manage campus-wide announcements">
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
                  className="form-control"
                  maxLength={255}
                  value={form.title}
                  onChange={(event) => setForm((current) => ({ ...current, title: event.target.value }))}
                  required
                />
              </div>
              <div className="col-md-6 col-lg-3">
                <label className="form-label fw-semibold" htmlFor="notice-category">Category</label>
                <select
                  id="notice-category"
                  className="form-select"
                  value={form.category}
                  onChange={(event) => setForm((current) => ({ ...current, category: event.target.value }))}
                >
                  <option>Academic</option>
                  <option>Exam</option>
                  <option>Holiday</option>
                  <option>Meeting</option>
                  <option>Facility</option>
                </select>
              </div>
              <div className="col-md-6 col-lg-3">
                <label className="form-label fw-semibold" htmlFor="notice-audience">Audience</label>
                <select
                  id="notice-audience"
                  className="form-select"
                  value={form.audience}
                  onChange={(event) => setForm((current) => ({ ...current, audience: event.target.value }))}
                >
                  <option>All</option>
                  <option>Students</option>
                  <option>Faculty</option>
                </select>
              </div>
              <div className="col-12">
                <label className="form-label fw-semibold" htmlFor="notice-description">Description</label>
                <textarea
                  id="notice-description"
                  className="form-control"
                  rows="4"
                  maxLength={2000}
                  value={form.description}
                  onChange={(event) => setForm((current) => ({ ...current, description: event.target.value }))}
                  required
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
                </div>
                <div className="admin-notice-actions">
                  <button type="button" className="btn btn-sm btn-link text-decoration-none" onClick={() => setSelectedNotice(notice)}>View</button>
                  <button type="button" className="btn btn-sm btn-link text-danger text-decoration-none" onClick={() => requestDelete(notice)}>Delete</button>
                </div>
              </article>
            ))}
          </div>
        ) : (
          <EmptyState title="No notices published" message="Use New Notice to publish the first campus announcement." />
        )}
      </Layout>

      <ModalDialog
        open={Boolean(selectedNotice)}
        title={selectedNotice?.title || 'Notice details'}
        onClose={() => setSelectedNotice(null)}
      >
        {selectedNotice && (
          <>
            <div className="d-flex flex-wrap gap-2 mb-3">
              {selectedNotice.tags.map((tag) => <span key={tag} className="course-pill course-pill-primary">{tag}</span>)}
            </div>
            <p className="text-secondary">{selectedNotice.description}</p>
            {selectedNotice.recipient_name && (
              <p className="mb-2">
                <strong>Recipient:</strong> {selectedNotice.recipient_name} ({selectedNotice.recipient_reference})
              </p>
            )}
            <small className="text-secondary">
              Published on {selectedNotice.date || 'Not dated'}
              {selectedNotice.author?.name ? ` by ${selectedNotice.author.name}` : ''}
            </small>
            <div className="modal-footer px-0 pb-0 mt-4">
              <button type="button" className="btn btn-outline-danger" onClick={() => requestDelete(selectedNotice)}>Delete</button>
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
