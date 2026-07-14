import { Link } from 'react-router-dom';
import Layout from '../components/Layout';

const icons = {
  users: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm11 10v-2a3 3 0 0 0-2-2.83M16 3.13a4 4 0 0 1 0 7.75',
  students: 'M4 10 12 5l8 5-8 5-8-5Zm3 3.5V18c2.8 2 7.2 2 10 0v-4.5M20 10v6',
  faculty: 'M4 5h16v14H4V5Zm4 4h8M8 13h5',
  alert: 'M12 3 22 20H2L12 3Zm0 6v5m0 3h.01',
  arrow: 'm9 18 6-6-6-6',
};

const stats = [
  { label: 'Total Users', value: '8,742', detail: '142 pending approval', tone: 'blue', icon: 'users', trend: '+6.8%' },
  { label: 'Active Students', value: '8,400', detail: 'This semester', tone: 'green', icon: 'students', trend: '+4.2%' },
  { label: 'Faculty Members', value: '340', detail: '52 departments', tone: 'amber', icon: 'faculty', trend: '+2.1%' },
  { label: 'System Alerts', value: '7', detail: 'Needs review', tone: 'red', icon: 'alert', trend: 'Action' },
];

const pendingActions = [
  { label: 'User Approvals', description: 'New accounts awaiting review', count: 5, tone: 'amber' },
  { label: 'Risk Alerts', description: 'Students requiring attention', count: 4, tone: 'red' },
  { label: 'Unread Reports', description: 'New administrative reports', count: 12, tone: 'blue' },
  { label: 'System Warnings', description: 'Platform health notifications', count: 3, tone: 'violet' },
];

const departments = [
  ['CSE', 92, 8],
  ['EEE', 81, 7],
  ['ME', 60, 5],
  ['Civil', 49, 4],
  ['Chem', 38, 3],
];

const activity = [
  ['New user registration', 'Lubna Akter · Student, CSE', '14 min ago', 'blue'],
  ['Notice published', 'Mid-Semester Examination Schedule', '42 min ago', 'green'],
  ['Risk alert generated', 'Nasrin Akter requires review', '1 hour ago', 'red'],
  ['Faculty account approved', 'Dr. Farhana Islam', '2 hours ago', 'violet'],
];

function AdminIcon({ name }) {
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d={icons[name]} />
    </svg>
  );
}

function AdminPage() {
  return (
    <Layout title="Admin Dashboard" subtitle="Northern University of Business and Technology | Khulna">
      <section className="admin-dashboard-hero mb-4">
        <div>
          <span className="admin-dashboard-eyebrow">ADMIN CONTROL CENTER</span>
          <h2>Good day, Administrator</h2>
          <p>Monitor campus operations, review priority items, and manage the university from one workspace.</p>
        </div>
        <div className="admin-live-status">
          <span />
          <div>
            <strong>All systems operational</strong>
            <small>Last checked just now</small>
          </div>
        </div>
      </section>

      <div className="row g-4 mb-4">
        {stats.map((item) => (
          <div key={item.label} className="col-sm-6 col-xl-3">
            <article className={`admin-kpi-card admin-kpi-${item.tone}`}>
              <div className="admin-kpi-header">
                <span className="admin-kpi-icon"><AdminIcon name={item.icon} /></span>
                <span className="admin-kpi-trend">{item.trend}</span>
              </div>
              <span className="admin-kpi-label">{item.label}</span>
              <strong>{item.value}</strong>
              <small>{item.detail}</small>
            </article>
          </div>
        ))}
      </div>

      <div className="row g-4 mb-4">
        <div className="col-xl-8">
          <section className="admin-dashboard-panel h-100">
            <div className="admin-panel-heading">
              <div>
                <span className="admin-panel-eyebrow">CAMPUS INSIGHT</span>
                <h3>User distribution</h3>
                <p>Student and faculty coverage by department</p>
              </div>
              <div className="admin-chart-legend">
                <span><i className="legend-student" />Students</span>
                <span><i className="legend-faculty" />Faculty</span>
              </div>
            </div>
            <div className="admin-department-chart admin-chart-polished" aria-label="User distribution by department preview">
              {departments.map(([label, students, faculty]) => (
                <div key={label} className="bar-chart-group">
                  <div className="bar-chart-bars admin-bars">
                    <span style={{ height: `${students}%` }} title={`${students}% students`} />
                    <span style={{ height: `${Math.max(faculty * 7, 22)}%` }} title={`${faculty}% faculty`} />
                  </div>
                  <strong>{label}</strong>
                </div>
              ))}
            </div>
          </section>
        </div>

        <div className="col-xl-4">
          <section className="admin-dashboard-panel h-100">
            <div className="admin-panel-heading">
              <div>
                <span className="admin-panel-eyebrow">REQUIRES ATTENTION</span>
                <h3>Pending actions</h3>
              </div>
              <span className="admin-total-badge">24 total</span>
            </div>
            <div className="admin-action-list admin-action-list-polished">
              {pendingActions.map((item) => (
                <div key={item.label} className={`admin-action-item admin-action-${item.tone}`}>
                  <span className="admin-action-dot" />
                  <div>
                    <strong>{item.label}</strong>
                    <small>{item.description}</small>
                  </div>
                  <span className="admin-action-count">{item.count}</span>
                </div>
              ))}
            </div>
            <Link to="/admin/users" className="btn btn-primary w-100 mt-4">
              Review pending users
              <AdminIcon name="arrow" />
            </Link>
          </section>
        </div>
      </div>

      <section className="admin-quick-actions mb-4">
        <div className="admin-panel-heading mb-3">
          <div>
            <span className="admin-panel-eyebrow">SHORTCUTS</span>
            <h3>Quick management</h3>
          </div>
        </div>
        <div className="row g-3">
          {[
            ['/admin/users', 'Manage users', 'Approve accounts and administrators', 'users'],
            ['/admin/notices', 'Manage notices', 'Publish campus announcements', 'faculty'],
            ['/admin/departments', 'Departments', 'Maintain academic departments', 'students'],
            ['/risk-alerts', 'Risk alerts', 'Review priority student alerts', 'alert'],
          ].map(([to, title, copy, icon]) => (
            <div key={to} className="col-sm-6 col-xl-3">
              <Link to={to} className="admin-quick-link">
                <span className="admin-quick-icon"><AdminIcon name={icon} /></span>
                <span>
                  <strong>{title}</strong>
                  <small>{copy}</small>
                </span>
                <AdminIcon name="arrow" />
              </Link>
            </div>
          ))}
        </div>
      </section>

      <section className="admin-dashboard-panel">
        <div className="admin-panel-heading mb-4">
          <div>
            <span className="admin-panel-eyebrow">AUDIT TRAIL</span>
            <h3>Recent system activity</h3>
            <p>Latest updates across your campus workspace</p>
          </div>
          <span className="admin-period-pill">Last 24 hours</span>
        </div>
        <div className="admin-activity-list admin-activity-timeline">
          {activity.map(([title, detail, time, tone]) => (
            <div key={title + detail} className="admin-activity-item">
              <span className={`admin-activity-marker marker-${tone}`} />
              <div>
                <strong>{title}</strong>
                <span>{detail}</span>
              </div>
              <small>{time}</small>
            </div>
          ))}
        </div>
      </section>
    </Layout>
  );
}

export default AdminPage;
