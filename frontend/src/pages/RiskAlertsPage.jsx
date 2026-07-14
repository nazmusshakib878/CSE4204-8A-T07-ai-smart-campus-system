import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import Layout from '../components/Layout';
import { EmptyState, LoadingState, ModalDialog, StatusAlert } from '../components/Feedback';
import { createNotice, getStudentMonitoring } from '../services/api';

function RiskAlertsPage() {
  const [monitoring, setMonitoring] = useState({
    students: [],
    summary: { high_risk: 0, medium_risk: 0, on_track: 0 },
  });
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState('');
  const [feedback, setFeedback] = useState(null);
  const [sendingId, setSendingId] = useState('');
  const [noticeStudent, setNoticeStudent] = useState(null);
  const [noticeMessage, setNoticeMessage] = useState('');
  const [sentStudentIds, setSentStudentIds] = useState([]);

  const fetchAlerts = useCallback(async () => {
    setLoading(true);
    setLoadError('');
    try {
      const response = await getStudentMonitoring();
      setMonitoring(response.data.data || { students: [], summary: {} });
    } catch (requestError) {
      setLoadError(requestError.message || 'Risk alerts could not be loaded.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchAlerts();
  }, [fetchAlerts]);

  const openNotice = (student) => {
    setNoticeStudent(student);
    setNoticeMessage(
      `Your academic record needs attention: ${student.risk_reason}. Please contact your faculty advisor to discuss the next steps.`
    );
  };

  const closeNotice = () => {
    if (sendingId) return;
    setNoticeStudent(null);
    setNoticeMessage('');
  };

  const handleSendNotice = async (event) => {
    event.preventDefault();
    if (!noticeStudent) return;

    const student = noticeStudent;
    setSendingId(student.id);
    setFeedback(null);

    try {
      await createNotice({
        title: `Academic notice for ${student.name}`,
        description: noticeMessage.trim(),
        category: 'Academic Risk',
        audience: 'Individual',
        recipient_name: student.name,
        recipient_reference: student.id,
      });
      setSentStudentIds((ids) => [...new Set([...ids, student.id])]);
      closeNotice();
      setFeedback({ variant: 'success', message: `Academic notice was sent to ${student.name}.` });
    } catch (requestError) {
      setFeedback({ variant: 'danger', message: requestError.message || 'The notice could not be sent.' });
    } finally {
      setSendingId('');
      setNoticeStudent(null);
      setNoticeMessage('');
    }
  };

  if (loading) {
    return (
      <Layout title="Risk Alerts" subtitle="Loading AI-assisted academic risk alerts...">
        <LoadingState message="Loading student risk data..." />
      </Layout>
    );
  }

  const riskStudents = monitoring.students.filter((student) => student.priority !== 'low');

  return (
    <>
      <Layout title="Risk Alerts" subtitle="Database-backed academic early warnings and AI analysis">
        {loadError && (
          <StatusAlert variant="danger" message={loadError} actionLabel="Try again" onAction={fetchAlerts} onDismiss={() => setLoadError('')} />
        )}
        {feedback && <StatusAlert variant={feedback.variant} message={feedback.message} onDismiss={() => setFeedback(null)} />}

        <div className="row g-4 mb-4">
          <div className="col-md-4"><div className="risk-summary risk-critical"><strong>{monitoring.summary.high_risk || 0}</strong><span>Critical</span></div></div>
          <div className="col-md-4"><div className="risk-summary risk-medium"><strong>{monitoring.summary.medium_risk || 0}</strong><span>Medium Risk</span></div></div>
          <div className="col-md-4"><div className="risk-summary risk-good"><strong>{monitoring.summary.on_track || 0}</strong><span>On Track</span></div></div>
        </div>

        {riskStudents.length > 0 ? (
          <div className="risk-alert-list">
            {riskStudents.map((student) => {
              const highRisk = student.priority === 'high';
              const wasSent = sentStudentIds.includes(student.id);
              return (
                <article key={student.database_id} className={`risk-alert-card ${highRisk ? 'is-high' : 'is-medium'}`}>
                  <div className="risk-alert-icon" aria-hidden="true">!</div>
                  <div className="risk-alert-main">
                    <div className="d-flex flex-wrap align-items-center gap-2 mb-1">
                      <h4>{student.name}</h4>
                      <span className={`status-pill ${highRisk ? 'status-critical' : 'status-risk'}`}>{student.risk_level}</span>
                      <span className="ai-source-badge">{student.analysis_source === 'openai' ? 'OPENAI' : 'BASELINE'}</span>
                    </div>
                    <small>ID: {student.id} · Risk score: {student.risk_score}/100</small>
                    <p>{student.prediction || student.risk_reason}</p>
                    <div className="risk-metrics">
                      <span><strong>{student.attendance}%</strong>Attendance</span>
                      <span><strong>{student.cgpa || '—'}</strong>CGPA</span>
                      <span><strong>{student.missed}</strong>Missed Classes</span>
                    </div>
                    {student.advice && <p className="risk-advice"><strong>AI advice:</strong> {student.advice}</p>}
                  </div>
                  <div className="risk-actions">
                    <button type="button" className={wasSent ? 'btn btn-success' : 'btn btn-primary'} onClick={() => openNotice(student)}>
                      {wasSent ? 'Send Again' : 'Send Notice'}
                    </button>
                    <Link to={`/student-monitoring?student=${encodeURIComponent(student.id)}`} className="btn btn-outline-secondary">
                      View & Analyze
                    </Link>
                  </div>
                </article>
              );
            })}
          </div>
        ) : (
          <EmptyState title="No students currently at risk" message="Risk alerts will appear when attendance, CGPA, or AI analysis identifies a concern." />
        )}
      </Layout>

      <ModalDialog open={Boolean(noticeStudent)} title={noticeStudent ? `Send notice to ${noticeStudent.name}` : 'Send notice'} onClose={closeNotice} closeDisabled={Boolean(sendingId)}>
        {noticeStudent && (
          <form onSubmit={handleSendNotice}>
            <p className="text-secondary">Review this academic follow-up before sending it to {noticeStudent.id}.</p>
            <label className="form-label fw-semibold" htmlFor="risk-notice-message">Notice message</label>
            <textarea id="risk-notice-message" className="form-control" rows="6" maxLength={1500} value={noticeMessage} onChange={(event) => setNoticeMessage(event.target.value)} required disabled={Boolean(sendingId)} />
            <div className="modal-footer px-0 pb-0 mt-4">
              <button type="button" className="btn btn-outline-secondary" onClick={closeNotice} disabled={Boolean(sendingId)}>Cancel</button>
              <button type="submit" className="btn btn-primary px-4" disabled={Boolean(sendingId) || !noticeMessage.trim()} aria-busy={Boolean(sendingId)}>
                {sendingId && <span className="spinner-border spinner-border-sm me-2" aria-hidden="true" />}
                {sendingId ? 'Sending...' : 'Send Notice'}
              </button>
            </div>
          </form>
        )}
      </ModalDialog>
    </>
  );
}

export default RiskAlertsPage;
