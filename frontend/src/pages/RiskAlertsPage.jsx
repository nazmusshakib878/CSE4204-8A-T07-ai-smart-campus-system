import { useState } from 'react';
import { Link } from 'react-router-dom';
import Layout from '../components/Layout';
import { ModalDialog, StatusAlert } from '../components/Feedback';
import { createNotice } from '../services/api';
import { riskStudents } from '../data/facultyStudents';

function RiskAlertsPage() {
  const [feedback, setFeedback] = useState(null);
  const [sendingId, setSendingId] = useState('');
  const [noticeStudent, setNoticeStudent] = useState(null);
  const [noticeMessage, setNoticeMessage] = useState('');
  const [sentStudentIds, setSentStudentIds] = useState([]);

  const openNotice = (student) => {
    setNoticeStudent(student);
    setNoticeMessage(
      `Your academic record needs attention: ${student.riskReason}. Please contact your faculty advisor to discuss the next steps.`
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

      setSentStudentIds((currentIds) => [...new Set([...currentIds, student.id])]);
      setNoticeStudent(null);
      setNoticeMessage('');
      setFeedback({ variant: 'success', message: `Academic notice was sent to ${student.name}.` });
    } catch (requestError) {
      setFeedback({ variant: 'danger', message: requestError.message || 'The notice could not be sent.' });
    } finally {
      setSendingId('');
    }
  };

  const criticalCount = riskStudents.filter((student) => student.priority === 'high').length;
  const mediumCount = riskStudents.filter((student) => student.priority === 'medium').length;

  return (
    <>
      <Layout title="Risk Alerts" subtitle="Students requiring immediate academic attention">
        {feedback && (
          <StatusAlert
            variant={feedback.variant}
            message={feedback.message}
            onDismiss={() => setFeedback(null)}
          />
        )}

        <div className="row g-4 mb-4">
          <div className="col-md-4"><div className="risk-summary risk-critical"><strong>{criticalCount}</strong><span>Critical</span></div></div>
          <div className="col-md-4"><div className="risk-summary risk-medium"><strong>{mediumCount}</strong><span>Medium Risk</span></div></div>
          <div className="col-md-4"><div className="risk-summary risk-good"><strong>138</strong><span>On Track</span></div></div>
        </div>

        <div className="risk-alert-list">
          {riskStudents.map((student) => {
            const highRisk = student.priority === 'high';
            const wasSent = sentStudentIds.includes(student.id);

            return (
              <article key={student.id} className={`risk-alert-card ${highRisk ? 'is-high' : 'is-medium'}`}>
                <div className="risk-alert-icon" aria-hidden="true">!</div>
                <div className="risk-alert-main">
                  <div className="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h4>{student.name}</h4>
                    <span className={`status-pill ${highRisk ? 'status-critical' : 'status-risk'}`}>{student.riskLevel}</span>
                  </div>
                  <small>ID: {student.id}</small>
                  <p>{student.riskReason}</p>
                  <div className="risk-metrics">
                    <span><strong>{student.attendance}%</strong>Attendance</span>
                    <span><strong>{student.cgpa}</strong>CGPA</span>
                    <span><strong>{student.missed}</strong>Missed Classes</span>
                  </div>
                </div>
                <div className="risk-actions">
                  <button
                    type="button"
                    className={wasSent ? 'btn btn-success' : 'btn btn-primary'}
                    onClick={() => openNotice(student)}
                  >
                    {wasSent ? 'Send Again' : 'Send Notice'}
                  </button>
                  <Link
                    to={`/student-monitoring?student=${encodeURIComponent(student.id)}`}
                    className="btn btn-outline-secondary"
                  >
                    View Profile
                  </Link>
                </div>
              </article>
            );
          })}
        </div>
      </Layout>

      <ModalDialog
        open={Boolean(noticeStudent)}
        title={noticeStudent ? `Send notice to ${noticeStudent.name}` : 'Send notice'}
        onClose={closeNotice}
        closeDisabled={Boolean(sendingId)}
      >
        {noticeStudent && (
          <form onSubmit={handleSendNotice}>
            <p className="text-secondary">
              Review the message before sending this academic follow-up to {noticeStudent.id}.
            </p>
            <label className="form-label fw-semibold" htmlFor="risk-notice-message">Notice message</label>
            <textarea
              id="risk-notice-message"
              className="form-control"
              rows="6"
              maxLength={1500}
              value={noticeMessage}
              onChange={(event) => setNoticeMessage(event.target.value)}
              required
              disabled={Boolean(sendingId)}
            />
            <div className="modal-footer px-0 pb-0 mt-4">
              <button type="button" className="btn btn-outline-secondary" onClick={closeNotice} disabled={Boolean(sendingId)}>
                Cancel
              </button>
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
