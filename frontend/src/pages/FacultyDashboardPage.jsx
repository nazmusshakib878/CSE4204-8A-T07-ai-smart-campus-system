import { Link } from 'react-router-dom';
import Layout from '../components/Layout';

const icons = {
  students: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm11 10v-2a3 3 0 0 0-2-2.83M16 3.13a4 4 0 0 1 0 7.75',
  calendar: 'M6 3v3m12-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z',
  attendance: 'm5 12 4 4L19 6',
  alert: 'M12 3 22 20H2L12 3Zm0 6v5m0 3h.01',
  arrow: 'm9 18 6-6-6-6',
};

const stats = [
  { label: 'Total Students', value: '142', detail: 'Across 3 courses', tone: 'blue', icon: 'students', trend: '+12 this term' },
  { label: 'Classes This Week', value: '8', detail: '2 labs · 6 lectures', tone: 'green', icon: 'calendar', trend: '4 remaining' },
  { label: 'Avg. Attendance', value: '83%', detail: 'This semester', tone: 'amber', icon: 'attendance', trend: '+2.4%' },
  { label: 'At-Risk Students', value: '4', detail: 'Needs attention', tone: 'red', icon: 'alert', trend: 'Review' },
];

const schedule = [
  { code: 'CSE 4105', title: 'Computer Networks', day: 'SUN', time: '9:00–10:30', room: 'Room 105', students: 48 },
  { code: 'CSE 4103', title: 'Artificial Intelligence', day: 'MON', time: '10:00–11:30', room: 'Room 202', students: 52 },
  { code: 'CSE 4103', title: 'Artificial Intelligence Lab', day: 'WED', time: '2:00–4:00', room: 'Lab 201', students: 26 },
  { code: 'CSE 4105', title: 'Computer Networks', day: 'THU', time: '9:00–10:30', room: 'Room 105', students: 48 },
];

const performance = [
  { label: 'CSE 4103 Avg. Score', value: 72, tone: 'blue' },
  { label: 'CSE 4105 Avg. Score', value: 68, tone: 'violet' },
  { label: 'Assignment Submission', value: 94, tone: 'green' },
  { label: 'Lab Report Submission', value: 88, tone: 'amber' },
];

function FacultyIcon({ name }) {
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d={icons[name]} />
    </svg>
  );
}

function FacultyDashboardPage() {
  return (
    <Layout title="Faculty Dashboard" subtitle="Dr. Nasrin Begum · Associate Professor, CSE Department">
      <section className="faculty-dashboard-hero mb-4">
        <div>
          <span className="faculty-hero-eyebrow">TEACHING WORKSPACE</span>
          <h2>Welcome back, Dr. Nasrin</h2>
          <p>Your classes, student progress, and priority academic actions are ready for review.</p>
        </div>
        <div className="faculty-next-class">
          <span>NEXT CLASS</span>
          <strong>Computer Networks</strong>
          <small>Sunday · 9:00 AM · Room 105</small>
        </div>
      </section>

      <div className="row g-4 mb-4">
        {stats.map((item) => (
          <div key={item.label} className="col-sm-6 col-xl-3">
            <article className={`faculty-kpi-card faculty-kpi-${item.tone}`}>
              <div className="faculty-kpi-top">
                <span className="faculty-kpi-icon"><FacultyIcon name={item.icon} /></span>
                <span className="faculty-kpi-trend">{item.trend}</span>
              </div>
              <span className="faculty-kpi-label">{item.label}</span>
              <strong>{item.value}</strong>
              <small>{item.detail}</small>
            </article>
          </div>
        ))}
      </div>

      <div className="row g-4 mb-4">
        <div className="col-xl-8">
          <section className="faculty-dashboard-panel h-100">
            <div className="faculty-panel-heading">
              <div>
                <span className="faculty-panel-eyebrow">WEEKLY AGENDA</span>
                <h3>Class schedule</h3>
                <p>Your upcoming lectures and lab sessions</p>
              </div>
              <span className="faculty-week-pill">This week</span>
            </div>
            <div className="faculty-schedule-list faculty-schedule-polished">
              {schedule.map((item) => (
                <article key={`${item.code}-${item.day}-${item.time}`} className="faculty-schedule-item">
                  <span className="faculty-day-badge">{item.day}</span>
                  <div className="faculty-class-copy">
                    <span>{item.code}</span>
                    <strong>{item.title}</strong>
                    <small>{item.time} · {item.room}</small>
                  </div>
                  <span className="faculty-student-count">{item.students} students</span>
                </article>
              ))}
            </div>
          </section>
        </div>

        <div className="col-xl-4">
          <section className="faculty-dashboard-panel h-100">
            <div className="faculty-panel-heading mb-4">
              <div>
                <span className="faculty-panel-eyebrow">ACADEMIC HEALTH</span>
                <h3>Course performance</h3>
                <p>Current semester snapshot</p>
              </div>
            </div>
            <div className="faculty-progress-list faculty-progress-polished">
              {performance.map((item) => (
                <div key={item.label}>
                  <div className="d-flex justify-content-between gap-3 mb-2">
                    <span>{item.label}</span>
                    <strong>{item.value}{item.label.includes('Score') ? '/100' : '%'}</strong>
                  </div>
                  <div className={`faculty-progress-track progress-${item.tone}`}>
                    <span style={{ width: `${item.value}%` }} />
                  </div>
                </div>
              ))}
            </div>
          </section>
        </div>
      </div>

      <section className="faculty-dashboard-panel">
        <div className="faculty-panel-heading mb-3">
          <div>
            <span className="faculty-panel-eyebrow">QUICK ACCESS</span>
            <h3>Student success tools</h3>
            <p>Monitor academic progress and reach students who need support.</p>
          </div>
        </div>
        <div className="row g-3">
          {[
            ['/student-monitoring', 'Student monitoring', 'Attendance, CGPA and activity', 'students'],
            ['/risk-alerts', 'Risk alerts', 'Review students needing attention', 'alert'],
            ['/notices/manage', 'Send notices', 'Publish academic communication', 'calendar'],
          ].map(([to, title, detail, icon]) => (
            <div key={to} className="col-md-4">
              <Link to={to} className="faculty-quick-link">
                <span className="faculty-quick-icon"><FacultyIcon name={icon} /></span>
                <span>
                  <strong>{title}</strong>
                  <small>{detail}</small>
                </span>
                <FacultyIcon name="arrow" />
              </Link>
            </div>
          ))}
        </div>
      </section>
    </Layout>
  );
}

export default FacultyDashboardPage;
