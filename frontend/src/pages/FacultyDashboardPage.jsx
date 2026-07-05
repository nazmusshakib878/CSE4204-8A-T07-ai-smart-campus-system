import { Link } from 'react-router-dom';
import Layout from '../components/Layout';

const stats = [
  { label: 'Total Students', value: '142', detail: 'Across 3 courses', tone: 'blue' },
  { label: 'Classes This Week', value: '8', detail: '2 labs, 6 lectures', tone: 'green' },
  { label: 'Avg. Attendance', value: '83%', detail: 'This semester', tone: 'amber' },
  { label: 'At-Risk Students', value: '4', detail: 'Needs attention', tone: 'red' },
];

const schedule = [
  { title: 'CSE 4105 - Computer Networks', meta: 'Sunday 9:00-10:30 | Room 105', students: 48 },
  { title: 'CSE 4103 - Artificial Intelligence', meta: 'Monday 10:00-11:30 | Room 202', students: 52 },
  { title: 'CSE 4103 Lab', meta: 'Wednesday 2:00-4:00 | Lab 201', students: 26 },
  { title: 'CSE 4105 - Computer Networks', meta: 'Thursday 9:00-10:30 | Room 105', students: 48 },
];

const performance = [
  { label: 'CSE 4103 Avg. Score', value: 72 },
  { label: 'CSE 4105 Avg. Score', value: 68 },
  { label: 'Assignment Submission Rate', value: 94 },
  { label: 'Lab Report Submission', value: 88 },
];

function FacultyDashboardPage() {
  return (
    <Layout title="Faculty Dashboard" subtitle="Dr. Nasrin Begum - Associate Professor, CSE Department">
      <div className="row g-4 mb-4">
        {stats.map((item) => (
          <div key={item.label} className="col-md-6 col-xl-3">
            <div className={`faculty-stat-card faculty-stat-${item.tone}`}>
              <span>{item.label}</span>
              <strong>{item.value}</strong>
              <small>{item.detail}</small>
            </div>
          </div>
        ))}
      </div>

      <div className="row g-4 mb-4">
        <div className="col-xl-8">
          <section className="faculty-panel h-100">
            <h4>Class Schedule - This Week</h4>
            <div className="faculty-schedule-list">
              {schedule.map((item) => (
                <article key={`${item.title}-${item.meta}`} className="faculty-schedule-item">
                  <div>
                    <strong>{item.title}</strong>
                    <span>{item.meta}</span>
                  </div>
                  <small>{item.students} students</small>
                </article>
              ))}
            </div>
          </section>
        </div>

        <div className="col-xl-4">
          <section className="faculty-panel h-100">
            <h4>Course Performance</h4>
            <div className="faculty-progress-list">
              {performance.map((item) => (
                <div key={item.label}>
                  <div className="d-flex justify-content-between gap-3 mb-2">
                    <span>{item.label}</span>
                    <strong>{item.value}{item.label.includes('Score') ? '/100' : '%'}</strong>
                  </div>
                  <div className="faculty-progress-track">
                    <span style={{ width: `${item.value}%` }} />
                  </div>
                </div>
              ))}
            </div>
          </section>
        </div>
      </div>

      <section className="faculty-panel">
        <div className="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
          <h4 className="mb-0">Student List</h4>
          <Link to="/student-monitoring" className="text-primary text-decoration-none fw-semibold">View full monitoring</Link>
        </div>
        <p className="text-secondary mb-0">Use Student Monitoring for attendance, CGPA, activity, and risk status.</p>
      </section>
    </Layout>
  );
}

export default FacultyDashboardPage;
