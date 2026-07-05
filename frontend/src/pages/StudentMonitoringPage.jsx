import { useMemo, useState } from 'react';
import Layout from '../components/Layout';

const students = [
  { id: '2021-1-60-001', name: 'Rafiqul Islam', attendance: 85, cgpa: 3.67, lastActive: 'Today', status: 'Good' },
  { id: '2021-1-60-002', name: 'Fatema Khanam', attendance: 62, cgpa: 2.45, lastActive: '3 days ago', status: 'Risk' },
  { id: '2021-1-60-003', name: 'Tanvir Ahmed', attendance: 91, cgpa: 3.82, lastActive: 'Today', status: 'Good' },
  { id: '2021-1-60-004', name: 'Nasrin Akter', attendance: 55, cgpa: 2.1, lastActive: '1 week ago', status: 'Critical' },
  { id: '2021-1-60-005', name: 'Sabbir Hossain', attendance: 88, cgpa: 3.4, lastActive: 'Yesterday', status: 'Good' },
];

function StudentMonitoringPage() {
  const [query, setQuery] = useState('');
  const filteredStudents = useMemo(() => (
    students.filter((student) => `${student.name} ${student.id}`.toLowerCase().includes(query.toLowerCase()))
  ), [query]);

  return (
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
                  <td><button type="button" className="btn btn-sm btn-link text-decoration-none">View</button></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>
    </Layout>
  );
}

export default StudentMonitoringPage;