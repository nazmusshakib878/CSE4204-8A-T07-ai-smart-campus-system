import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import Layout from '../components/Layout';
import { EmptyState, LoadingState, ModalDialog, StatusAlert } from '../components/Feedback';
import { analyzeStudentRisk, getStudentMonitoring } from '../services/api';

const emptyMonitoring = {
  students: [],
  charts: { performance_trend: [], score_distribution: [] },
  summary: { total: 0, high_risk: 0, medium_risk: 0, on_track: 0 },
  ai_configured: false,
};

function chartPoints(items, key) {
  if (!items.length) return '';
  const width = 575 / Math.max(1, items.length - 1);
  return items.map((item, index) => {
    const value = Math.max(0, Math.min(100, Number(item[key]) || 0));
    return `${40 + index * width},${220 - value * 1.85}`;
  }).join(' ');
}

function StudentMonitoringPage() {
  const [monitoring, setMonitoring] = useState(emptyMonitoring);
  const [query, setQuery] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [feedback, setFeedback] = useState(null);
  const [analyzingId, setAnalyzingId] = useState(null);
  const [searchParams, setSearchParams] = useSearchParams();

  const fetchMonitoring = useCallback(async ({ quiet = false } = {}) => {
    if (!quiet) setLoading(true);
    setError('');

    try {
      const response = await getStudentMonitoring();
      setMonitoring(response.data.data || emptyMonitoring);
    } catch (requestError) {
      setError(requestError.message || 'Student monitoring data could not be loaded.');
    } finally {
      if (!quiet) setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchMonitoring();
  }, [fetchMonitoring]);

  const selectedStudent = monitoring.students.find(
    (student) => student.id === searchParams.get('student')
  );

  const filteredStudents = useMemo(() => (
    monitoring.students.filter((student) => (
      `${student.id} ${student.name}`.toLowerCase().includes(query.trim().toLowerCase())
    ))
  ), [monitoring.students, query]);

  const handleAnalyze = async (student) => {
    setAnalyzingId(student.database_id);
    setFeedback(null);

    try {
      await analyzeStudentRisk(student.database_id);
      await fetchMonitoring({ quiet: true });
      setFeedback({ variant: 'success', message: `AI risk analysis completed for ${student.name}.` });
    } catch (requestError) {
      setFeedback({ variant: 'danger', message: requestError.message || 'AI analysis could not be completed.' });
    } finally {
      setAnalyzingId(null);
    }
  };

  const trend = monitoring.charts.performance_trend || [];
  const distribution = monitoring.charts.score_distribution || [];

  if (loading) {
    return (
      <Layout title="Student Monitoring" subtitle="Loading attendance and academic performance...">
        <LoadingState message="Loading faculty student data..." />
      </Layout>
    );
  }

  return (
    <>
      <Layout title="Student Monitoring" subtitle="Real attendance, performance, and AI-assisted academic risk analysis.">
        {error && (
          <StatusAlert
            variant="danger"
            message={error}
            actionLabel="Try again"
            onAction={() => fetchMonitoring()}
            onDismiss={() => setError('')}
          />
        )}
        {feedback && (
          <StatusAlert
            variant={feedback.variant}
            message={feedback.message}
            onDismiss={() => setFeedback(null)}
          />
        )}

        <div className="row g-3 mb-4">
          {[
            ['Students', monitoring.summary.total, 'blue'],
            ['High risk', monitoring.summary.high_risk, 'red'],
            ['Medium risk', monitoring.summary.medium_risk, 'amber'],
            ['On track', monitoring.summary.on_track, 'green'],
          ].map(([label, value, tone]) => (
            <div key={label} className="col-6 col-xl-3">
              <div className={`risk-summary monitoring-summary monitoring-${tone}`}>
                <strong>{value}</strong><span>{label}</span>
              </div>
            </div>
          ))}
        </div>

        {!monitoring.ai_configured && (
          <StatusAlert
            variant="warning"
            message="AI analysis is ready but OPENAI_API_KEY is not configured in backend/.env. Baseline risk scoring remains available."
          />
        )}

        <div className="row g-4 mb-4">
          <div className="col-xl-6">
            <section className="faculty-panel h-100">
              <div className="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                  <h4 className="mb-1">Class Performance Trend</h4>
                  <small className="text-secondary">Database-derived monthly averages</small>
                </div>
                <span className="ai-source-badge">LIVE DATA</span>
              </div>
              {trend.length > 0 ? (
                <div className="trend-chart" aria-label="Class performance trend">
                  <svg viewBox="0 0 640 260" role="img">
                    <path className="chart-grid" d="M40 35H615M40 90H615M40 145H615M40 200H615" />
                    <polyline className="chart-line-primary" fill="none" points={chartPoints(trend, 'score')} />
                    <polyline className="chart-line-secondary" fill="none" points={chartPoints(trend, 'attendance')} />
                    <text x="28" y="38">100</text><text x="34" y="93">75</text>
                    <text x="34" y="148">50</text><text x="34" y="203">25</text>
                    {trend.map((item, index) => (
                      <text key={item.label} x={40 + index * (575 / Math.max(1, trend.length - 1))} y="245">
                        {item.label}
                      </text>
                    ))}
                  </svg>
                  <div className="monitoring-chart-legend">
                    <span><i className="legend-score" />Average score</span>
                    <span><i className="legend-attendance" />Attendance</span>
                  </div>
                </div>
              ) : (
                <EmptyState title="No trend data yet" message="Add attendance and academic records to generate this chart." />
              )}
            </section>
          </div>

          <div className="col-xl-6">
            <section className="faculty-panel h-100">
              <div className="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                  <h4 className="mb-1">Score Distribution by Course</h4>
                  <small className="text-secondary">Average and highest recorded scores</small>
                </div>
                <span className="ai-source-badge">LIVE DATA</span>
              </div>
              {distribution.length > 0 ? (
                <div className="bar-chart" aria-label="Score distribution by course">
                  {distribution.map((item) => (
                    <div key={item.label} className="bar-chart-group">
                      <div className="bar-chart-bars">
                        <span style={{ height: `${item.average}%` }} title={`Average ${item.average}%`} />
                        <span style={{ height: `${item.highest}%` }} title={`Highest ${item.highest}%`} />
                      </div>
                      <small>{item.label}</small>
                    </div>
                  ))}
                </div>
              ) : (
                <EmptyState title="No course scores yet" message="Academic records will populate this chart automatically." />
              )}
            </section>
          </div>
        </div>

        <section className="faculty-panel">
          <div className="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div>
              <h4 className="mb-1">All Students</h4>
              <small className="text-secondary">Only students assigned to your courses or department are shown.</small>
            </div>
            <label className="student-search">
              <span className="visually-hidden">Search student</span>
              <input
                type="search"
                className="form-control"
                placeholder="Search University ID or name..."
                value={query}
                onChange={(event) => setQuery(event.target.value)}
              />
            </label>
          </div>

          {filteredStudents.length > 0 ? (
            <div className="table-responsive">
              <table className="table faculty-table align-middle">
                <thead>
                  <tr>
                    <th>Student ID</th><th>Name</th><th>Attendance</th><th>CGPA</th>
                    <th>Risk Score</th><th>Status</th><th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  {filteredStudents.map((student) => (
                    <tr key={student.database_id}>
                      <td><strong>{student.id}</strong></td>
                      <td><strong>{student.name}</strong><small className="d-block text-secondary">{student.department}</small></td>
                      <td>
                        <div className="attendance-cell">
                          <span className={student.attendance < 65 ? 'is-risk' : ''} style={{ width: `${student.attendance}%` }} />
                          <strong>{student.attendance}%</strong>
                        </div>
                      </td>
                      <td>{student.cgpa || '—'}</td>
                      <td><strong>{student.risk_score}/100</strong><small className="d-block text-secondary">{student.analysis_source === 'openai' ? 'OpenAI' : 'Baseline'}</small></td>
                      <td><span className={`status-pill status-${student.status.toLowerCase()}`}>{student.status}</span></td>
                      <td>
                        <button type="button" className="btn btn-sm btn-link text-decoration-none" onClick={() => setSearchParams({ student: student.id })}>
                          View
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <EmptyState
              title={query ? 'No students found' : 'No assigned students yet'}
              message={query ? `No student matches "${query.trim()}".` : 'Assign courses and add academic records to populate monitoring.'}
            />
          )}
        </section>
      </Layout>

      <ModalDialog
        open={Boolean(selectedStudent)}
        title={selectedStudent ? selectedStudent.name : 'Student details'}
        onClose={() => setSearchParams({})}
        closeDisabled={Boolean(analyzingId)}
      >
        {selectedStudent && (
          <>
            <div className="d-flex flex-wrap align-items-center gap-2 mb-4">
              <span className={`status-pill status-${selectedStudent.status.toLowerCase()}`}>{selectedStudent.status}</span>
              <span className="text-secondary">{selectedStudent.id}</span>
              <span className="ai-source-badge">{selectedStudent.analysis_source === 'openai' ? 'OPENAI ANALYSIS' : 'BASELINE SCORE'}</span>
            </div>
            <div className="row g-3 mb-4">
              {[
                ['Attendance', `${selectedStudent.attendance}%`],
                ['CGPA', selectedStudent.cgpa || 'No data'],
                ['Missed Classes', selectedStudent.missed],
                ['Risk Score', `${selectedStudent.risk_score}/100`],
              ].map(([label, value]) => (
                <div key={label} className="col-sm-6">
                  <div className="profile-info-tile h-100"><span>{label}</span><strong>{value}</strong></div>
                </div>
              ))}
            </div>
            <div className="ai-risk-explanation mb-4">
              <strong>{selectedStudent.prediction || selectedStudent.risk_reason}</strong>
              <ul>
                {(selectedStudent.risk_reasons || []).map((reason) => <li key={reason}>{reason}</li>)}
              </ul>
              {selectedStudent.advice && <p><b>Suggested action:</b> {selectedStudent.advice}</p>}
            </div>
            <dl className="row mb-4">
              <dt className="col-sm-4">Email</dt><dd className="col-sm-8">{selectedStudent.email || '—'}</dd>
              <dt className="col-sm-4">Department</dt><dd className="col-sm-8">{selectedStudent.department || '—'}</dd>
              <dt className="col-sm-4">Courses</dt><dd className="col-sm-8">{selectedStudent.courses.join(', ') || 'No assigned courses'}</dd>
            </dl>
            <div className="modal-footer px-0 pb-0">
              {selectedStudent.priority !== 'low' && <Link to="/risk-alerts" className="btn btn-outline-danger">Open Risk Alerts</Link>}
              <button
                type="button"
                className="btn btn-primary px-4"
                disabled={Boolean(analyzingId) || !monitoring.ai_configured}
                onClick={() => handleAnalyze(selectedStudent)}
                aria-busy={analyzingId === selectedStudent.database_id}
              >
                {analyzingId === selectedStudent.database_id && <span className="spinner-border spinner-border-sm me-2" />}
                {analyzingId === selectedStudent.database_id ? 'Analyzing...' : 'Analyze with AI'}
              </button>
            </div>
          </>
        )}
      </ModalDialog>
    </>
  );
}

export default StudentMonitoringPage;
