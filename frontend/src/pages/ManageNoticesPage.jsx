import { useState } from 'react';
import Layout from '../components/Layout';
import { StatusAlert } from '../components/Feedback';

const initialNotices = [
  { id: 1, title: 'Mid-Semester Examination Schedule', date: '2026-06-15', tags: ['Pinned', 'Exam', 'All'] },
  { id: 2, title: 'Holiday Notice - Eid-ul-Adha 2026', date: '2026-06-12', tags: ['Pinned', 'Holiday', 'All'] },
  { id: 3, title: 'New Course Registration Opens July 1', date: '2026-06-10', tags: ['Academic', 'Students'] },
  { id: 4, title: 'Faculty Meeting - Department of CSE', date: '2026-06-08', tags: ['Meeting', 'Faculty'] },
  { id: 5, title: 'Library Maintenance Notice', date: '2026-06-06', tags: ['Facility', 'All'] },
];

function ManageNoticesPage() {
  const [notices, setNotices] = useState(initialNotices);
  const [showForm, setShowForm] = useState(false);
  const [feedback, setFeedback] = useState(null);
  const [form, setForm] = useState({ title: '', category: 'Academic', audience: 'All' });

  const handleSubmit = (event) => {
    event.preventDefault();
    const notice = {
      id: Date.now(),
      title: form.title.trim(),
      date: new Date().toISOString().slice(0, 10),
      tags: [form.category, form.audience],
    };
    setNotices((currentNotices) => [notice, ...currentNotices]);
    setForm({ title: '', category: 'Academic', audience: 'All' });
    setShowForm(false);
    setFeedback({ variant: 'success', message: 'Notice created successfully.' });
  };

  const handleDelete = (notice) => {
    setNotices((currentNotices) => currentNotices.filter((item) => item.id !== notice.id));
    setFeedback({ variant: 'warning', message: `${notice.title} was removed.` });
  };

  return (
    <Layout title="Manage Notices" subtitle="Create and manage campus-wide announcements">
      {feedback && (
        <StatusAlert
          variant={feedback.variant}
          message={feedback.message}
          onDismiss={() => setFeedback(null)}
        />
      )}

      <div className="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <span className="admin-total-count">{notices.length} notices total</span>
        <button type="button" className="btn btn-primary px-4" onClick={() => setShowForm((open) => !open)}>
          + New Notice
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
                value={form.title}
                onChange={(event) => setForm((current) => ({ ...current, title: event.target.value }))}
                required
              />
            </div>
            <div className="col-md-6 col-lg-2">
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
            <div className="col-md-6 col-lg-2">
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
            <div className="col-lg-2">
              <button type="submit" className="btn btn-primary w-100">Publish</button>
            </div>
          </div>
        </form>
      )}

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
              <button type="button" className="btn btn-sm btn-link text-decoration-none">View</button>
              <button type="button" className="btn btn-sm btn-link text-danger text-decoration-none" onClick={() => handleDelete(notice)}>Delete</button>
            </div>
          </article>
        ))}
      </div>
    </Layout>
  );
}

export default ManageNoticesPage;