import { useState } from 'react';
import Layout from '../components/Layout';
import { StatusAlert } from '../components/Feedback';
import { createTask } from '../services/api';

const riskStudents = [
  { id: '2021-1-60-002', name: 'Fatema Khanam', level: 'Medium Risk', reason: 'Low attendance below 65% threshold', attendance: 62, cgpa: 2.45, missed: 12, priority: 'medium' },
  { id: '2021-1-60-004', name: 'Nasrin Akter', level: 'High Risk', reason: 'Critical attendance + CGPA below 2.50 minimum', attendance: 55, cgpa: 2.1, missed: 18, priority: 'high' },
  { id: '2021-1-60-006', name: 'Moriom Begum', level: 'Medium Risk', reason: 'Below average performance across all courses', attendance: 68, cgpa: 2.65, missed: 10, priority: 'medium' },
];

function RiskAlertsPage() {
  const [feedback, setFeedback] = useState(null);
  const [sendingId, setSendingId] = useState('');

  const handleSendNotice = async (student) => {
    setSendingId(student.id);
    setFeedback(null);

    try {
      await createTask({
        title: `Academic notice for ${student.name}`,
        description: `${student.level}: ${student.reason}. Attendance ${student.attendance}%, CGPA ${student.cgpa}, missed classes ${student.missed}.`,
        assigned_to: student.name,
        due_date: '',
        status: 'pending',
        priority: student.priority,
      });

      setFeedback({ variant: 'success', message: `Notice task created for ${student.name}.` });
    } catch (requestError) {
      setFeedback({ variant: 'danger', message: requestError.message || 'Notice task could not be created.' });
    } finally {
      setSendingId('');
    }
  };

  return (
    <Layout title="Risk Alerts" subtitle="Students requiring immediate academic attention">
      {feedback && (
        <StatusAlert
          variant={feedback.variant}
          message={feedback.message}
          onDismiss={() => setFeedback(null)}
        />
      )}

      <div className="row g-4 mb-4">
        <div className="col-md-4"><div className="risk-summary risk-critical"><strong>1</strong><span>Critical</span></div></div>
        <div className="col-md-4"><div className="risk-summary risk-medium"><strong>3</strong><span>Medium Risk</span></div></div>
        <div className="col-md-4"><div className="risk-summary risk-good"><strong>138</strong><span>On Track</span></div></div>
      </div>

      <div className="risk-alert-list">
        {riskStudents.map((student) => {
          const highRisk = student.priority === 'high';
          const isSending = sendingId === student.id;

          return (
            <article key={student.id} className={`risk-alert-card ${highRisk ? 'is-high' : 'is-medium'}`}>
              <div className="risk-alert-icon" aria-hidden="true">!</div>
              <div className="risk-alert-main">
                <div className="d-flex flex-wrap align-items-center gap-2 mb-1">
                  <h4>{student.name}</h4>
                  <span className={`status-pill ${highRisk ? 'status-critical' : 'status-risk'}`}>{student.level}</span>
                </div>
                <small>ID: {student.id}</small>
                <p>{student.reason}</p>
                <div className="risk-metrics">
                  <span><strong>{student.attendance}%</strong>Attendance</span>
                  <span><strong>{student.cgpa}</strong>CGPA</span>
                  <span><strong>{student.missed}</strong>Missed Classes</span>
                </div>
              </div>
              <div className="risk-actions">
                <button
                  type="button"
                  className="btn btn-primary"
                  onClick={() => handleSendNotice(student)}
                  disabled={isSending}
                  aria-busy={isSending}
                >
                  {isSending ? 'Sending...' : 'Send Notice'}
                </button>
                <button type="button" className="btn btn-outline-secondary">View Profile</button>
              </div>
            </article>
          );
        })}
      </div>
    </Layout>
  );
}

export default RiskAlertsPage;