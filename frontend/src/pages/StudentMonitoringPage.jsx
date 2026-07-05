import { useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import Layout from '../components/Layout';
import { EmptyState, ModalDialog } from '../components/Feedback';
import { facultyStudents } from '../data/facultyStudents';

function StudentMonitoringPage() {
  const [query, setQuery] = useState('');
  const [searchParams, setSearchParams] = useSearchParams();
  const selectedStudent = facultyStudents.find((student) => student.id === searchParams.get('student'));
  const filteredStudents = useMemo(() => (
    facultyStudents.filter((student) => `${student.name} ${student.id}`.toLowerCase().includes(query.trim().toLowerCase()))
  ), [query]);

  const showStudent = (student) => {
    setSearchParams({ student: student.id });
  };

  const closeStudent = () => {
    setSearchParams({});
  };

  return (
    <>
      <Layout title="Student Monitoring" subtitle="Track attendance, scores, and academic activity across your courses.">
        <div className="row g-4 mb-4">
          <div className="col-xl-6">
            <section className="faculty-panel h-100">
              <h4>Class Performance Trend</h4>
              <div className="trend-chart" aria-label="Class performance trend preview">
                <svg viewBox="0 0 640 260" role="img">
                  <path className="chart-grid" d="M40 35H615M40 90H615M40 145H615M40 200H615M40 35V220M160 35V220M280 35V220M400 35V220M520 35V220" />
                  <path className="chart-area" d="M40 82 C120 92 165 98 240 78 S360 126 450 92 S560 92 615 98 L615 220 L40 220Z" />
                  <path className="chart-line-primary" d="M40 82 C120 92 165 98 240 78 S360 126 450 92 S560 92 615 98" />
                  <path className="chart-line-secondary" d="M40 112 C115 122 170 128 240 108 S355 152 450 122 S560 122 615 132" />
                  <text x="28" y="38">100</text><text x="34" y="93">75</text><text x="34" y="148">50</text><text x="34" y="203">25</text>
                  <text x="40" y="245">Jan</text><text x="160" y="245">Feb</text><text x="280" y="245">Mar</text><text x="400" y="245">Apr</text><text x="520" y="245">May</text><text x="605" y="245">Jun</text>
                </svg>
              </div>
            </section>
          </div>

          <div className="col-xl-6">
            <section className="faculty-panel h-100">
              <h4>Score Distribution by Course</h4>
              <div className="bar-chart" aria-label="Score distribution by course preview">
                {[
                  ['CSE 4101', 38, 72],
                  ['CSE 4103', 42, 68],
                  ['CSE 4105', 35, 75],
                  ['MATH 3201', 30, 60],
                  ['CSE 4107', 44, 81],
                ].map(([label, first, second]) => (
                  <div key={label} className="bar-chart-group">
                    <div className="bar-chart-bars">
                      <span style={{ height: `${first}%` }} />
                      <span style={{ height: `${second}%` }} />
                    </div>
                    <small>{label}</small>
                  </div>
                ))}
              </div>
            </section>
          </div>
        </div>

        <section className="faculty-panel">
          <div className="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <h4 className="mb-0">All Students</h4>
            <label className="student-search">
              <span className="visually-hidden">Search student</span>
              <input
                type="search"
                className="form-control"
                placeholder="Search name or ID..."
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
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Attendance</th>
                    <th>CGPA</th>
                    <th>Last Active</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  {filteredStudents.map((student) => (
                    <tr key={student.id}>
                      <td>{student.id}</td>
                      <td><strong>{student.name}</strong></td>
                      <td>
                        <div className="attendance-cell">
                          <span className={student.attendance < 65 ? 'is-risk' : ''} style={{ width: `${student.attendance}%` }} />
                          <strong>{student.attendance}%</strong>
                        </div>
                      </td>
                      <td>{student.cgpa}</td>
                      <td>{student.lastActive}</td>
                      <td><span className={`status-pill status-${student.status.toLowerCase()}`}>{student.status}</span></td>
                      <td>
                        <button
                          type="button"
                          className="btn btn-sm btn-link text-decoration-none"
                          onClick={() => showStudent(student)}
                        >
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
              title="No students found"
              message={`No student matches "${query.trim()}". Try a different name or ID.`}
            />
          )}
        </section>
      </Layout>

      <ModalDialog
        open={Boolean(selectedStudent)}
        title={selectedStudent ? selectedStudent.name : 'Student details'}
        onClose={closeStudent}
      >
        {selectedStudent && (
          <>
            <div className="d-flex flex-wrap align-items-center gap-2 mb-4">
              <span className={`status-pill status-${selectedStudent.status.toLowerCase()}`}>{selectedStudent.status}</span>
              <span className="text-secondary">{selectedStudent.id}</span>
            </div>
            <div className="row g-3 mb-4">
              {[
                ['Attendance', `${selectedStudent.attendance}%`],
                ['CGPA', selectedStudent.cgpa],
                ['Missed Classes', selectedStudent.missed],
                ['Last Active', selectedStudent.lastActive],
              ].map(([label, value]) => (
                <div key={label} className="col-sm-6">
                  <div className="profile-info-tile h-100">
                    <span>{label}</span>
                    <strong>{value}</strong>
                  </div>
                </div>
              ))}
            </div>
            <dl className="row mb-4">
              <dt className="col-sm-4">Email</dt>
              <dd className="col-sm-8">{selectedStudent.email}</dd>
              <dt className="col-sm-4">Department</dt>
              <dd className="col-sm-8">{selectedStudent.department}</dd>
              <dt className="col-sm-4">Semester</dt>
              <dd className="col-sm-8">{selectedStudent.semester}</dd>
              <dt className="col-sm-4">Courses</dt>
              <dd className="col-sm-8">{selectedStudent.courses.join(', ')}</dd>
              {selectedStudent.riskReason && (
                <>
                  <dt className="col-sm-4">Risk reason</dt>
                  <dd className="col-sm-8">{selectedStudent.riskReason}</dd>
                </>
              )}
            </dl>
            <div className="modal-footer px-0 pb-0">
              {selectedStudent.riskLevel && (
                <Link to="/risk-alerts" className="btn btn-outline-danger">Open Risk Alerts</Link>
              )}
              <button type="button" className="btn btn-primary px-4" onClick={closeStudent}>Close</button>
            </div>
          </>
        )}
      </ModalDialog>
    </>
  );
}

export default StudentMonitoringPage;
