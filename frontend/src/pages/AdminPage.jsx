import { Link } from 'react-router-dom';
import Layout from '../components/Layout';

const stats = [
  { label: 'Total Users', value: '8,742', detail: '142 pending approval', tone: 'blue' },
  { label: 'Active Students', value: '8,400', detail: 'This semester', tone: 'green' },
  { label: 'Faculty Members', value: '340', detail: '52 departments', tone: 'amber' },
  { label: 'System Alerts', value: '7', detail: 'Needs review', tone: 'red' },
];

const pendingActions = [
  { label: 'User Approvals', count: 5, tone: 'amber' },
  { label: 'Risk Alerts', count: 4, tone: 'red' },
  { label: 'Unread Reports', count: 12, tone: 'blue' },
  { label: 'System Warnings', count: 3, tone: 'violet' },
];

const activity = [
  ['New user registration - Lubna Akter (Student, CSE)', '14 min ago'],
  ['Notice published - Mid-Semester Examination Schedule', '42 min ago'],
  ['Risk alert generated for Nasrin Akter', '1 hour ago'],
  ['Faculty account approved - Dr. Farhana Islam', '2 hours ago'],
];

function AdminPage() {
  return (
    <Layout title="Admin Dashboard" subtitle="System overview - Dhaka University of Technology Campus">
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
            <h4>User Distribution by Department</h4>
            <div className="admin-department-chart" aria-label="User distribution by department preview">
              {[
                ['CSE', 92, 8],
                ['EEE', 81, 7],
                ['ME', 60, 5],
                ['Civil', 49, 4],
                ['Chem', 38, 3],
              ].map(([label, students, faculty]) => (
                <div key={label} className="bar-chart-group">
                  <div className="bar-chart-bars admin-bars">
                    <span style={{ height: `${students}%` }} />
                    <span style={{ height: `${faculty}%` }} />
                  </div>
                  <small>{label}</small>
                </div>
              ))}
            </div>
          </section>
        </div>

        <div className="col-xl-4">
          <section className="faculty-panel h-100">
            <h4>Pending Actions</h4>
            <div className="admin-action-list">
              {pendingActions.map((item) => (
                <div key={item.label} className={`admin-action-item admin-action-${item.tone}`}>
                  <strong>{item.label}</strong>
                  <span>{item.count}</span>
                </div>
              ))}
            </div>
            <Link to="/admin/users" className="btn btn-outline-primary w-100 mt-4">Review Pending Users</Link>
          </section>
        </div>
      </div>

      <section className="faculty-panel">
        <div className="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
          <h4 className="mb-0">Recent System Activity</h4>
          <span className="text-secondary small">Last 24 hours</span>
        </div>
        <div className="admin-activity-list">
          {activity.map(([label, time]) => (
            <div key={label} className="admin-activity-item">
              <span>{label}</span>
              <small>{time}</small>
            </div>
          ))}
        </div>
      </section>
    </Layout>
  );
}

export default AdminPage;
