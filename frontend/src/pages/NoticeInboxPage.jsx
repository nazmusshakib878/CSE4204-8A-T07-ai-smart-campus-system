import { useCallback, useEffect, useMemo, useState } from 'react';
import Layout from '../components/Layout';
import { EmptyState, LoadingState, ModalDialog, StatusAlert } from '../components/Feedback';
import { getNotices } from '../services/api';
import { useAuth } from '../auth/auth-context';

const readKeyFor = (user) => `notice_read_ids_${user?.id || user?.email || 'guest'}`;

const normalizeNotice = (notice) => ({
  ...notice,
  date: (notice.publish_date || notice.created_at || '').slice(0, 10),
  tags: [notice.category || 'Academic', notice.audience || 'All', notice.target_department, notice.target_role, notice.target_semester]
    .filter(Boolean),
});

function NoticeInboxPage() {
  const { user } = useAuth();
  const [notices, setNotices] = useState([]);
  const [readIds, setReadIds] = useState([]);
  const [selectedNotice, setSelectedNotice] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const readStorageKey = useMemo(() => readKeyFor(user), [user]);
  const unreadCount = notices.filter((notice) => !readIds.includes(String(notice.id))).length;

  const fetchNotices = useCallback(async () => {
    setLoading(true);
    setError('');

    try {
      const response = await getNotices();
      setNotices((response.data.data || []).map(normalizeNotice));
    } catch (requestError) {
      setError(requestError.message || 'Messages could not be loaded.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    setReadIds(JSON.parse(localStorage.getItem(readStorageKey) || '[]'));
    fetchNotices();
  }, [fetchNotices, readStorageKey]);

  useEffect(() => {
    if (loading || notices.length === 0) return;
    const nextReadIds = Array.from(new Set([...readIds, ...notices.map((notice) => String(notice.id))]));
    localStorage.setItem(readStorageKey, JSON.stringify(nextReadIds));
    setReadIds(nextReadIds);
    window.dispatchEvent(new Event('notice-read-updated'));
  }, [loading, notices, readIds, readStorageKey]);

  return (
    <Layout title="Messages" subtitle="Notices delivered to your account">
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
        <span className="admin-total-count">{unreadCount} new messages</span>
      </div>

      {loading ? (
        <LoadingState message="Loading messages..." />
      ) : notices.length > 0 ? (
        <div className="admin-notice-list">
          {notices.map((notice) => (
            <article key={notice.id} className="admin-notice-card">
              <div className="min-w-0">
                <div className="d-flex flex-wrap gap-2 mb-2">
                  {notice.tags.map((tag) => <span key={tag} className="course-pill course-pill-primary">{tag}</span>)}
                </div>
                <h4>{notice.title}</h4>
                <small>{notice.date}{notice.author?.name ? ` by ${notice.author.name}` : ''}</small>
                {notice.attachment_url && (
                  <a className="d-inline-block mt-2 text-primary fw-semibold" href={notice.attachment_url} target="_blank" rel="noreferrer">
                    {notice.attachment_name || 'Open due list PDF'}
                  </a>
                )}
              </div>
              <div className="admin-notice-actions">
                <button type="button" className="btn btn-sm btn-link text-decoration-none" onClick={() => setSelectedNotice(notice)}>View</button>
              </div>
            </article>
          ))}
        </div>
      ) : (
        <EmptyState title="No messages" message="Notices for your account will appear here." />
      )}

      <ModalDialog open={Boolean(selectedNotice)} title={selectedNotice?.title || 'Message'} onClose={() => setSelectedNotice(null)}>
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
              <button type="button" className="btn btn-primary px-4" onClick={() => setSelectedNotice(null)}>Close</button>
            </div>
          </>
        )}
      </ModalDialog>
    </Layout>
  );
}

export default NoticeInboxPage;