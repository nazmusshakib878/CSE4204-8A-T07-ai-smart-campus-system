import { useMemo, useState } from 'react';
import Layout from '../components/Layout';
import { StatusAlert } from '../components/Feedback';

const pendingUsers = [
  { id: 1, name: 'Arif Hossain', email: 'arif@student.dut.ac.bd', role: 'Student', department: 'CSE', appliedOn: '2026-06-18' },
  { id: 2, name: 'Dr. Farhana Islam', email: 'farhana@faculty.dut.ac.bd', role: 'Faculty', department: 'EEE', appliedOn: '2026-06-17' },
  { id: 3, name: 'Shakib Rahman', email: 'shakib@student.dut.ac.bd', role: 'Student', department: 'ME', appliedOn: '2026-06-19' },
  { id: 4, name: 'Prof. Mahbub Ali', email: 'mahbub@faculty.dut.ac.bd', role: 'Faculty', department: 'Civil', appliedOn: '2026-06-16' },
  { id: 5, name: 'Lubna Akter', email: 'lubna@student.dut.ac.bd', role: 'Student', department: 'CSE', appliedOn: '2026-06-20' },
];

function ManageUsersPage() {
  const [filter, setFilter] = useState('All');
  const [users, setUsers] = useState(pendingUsers);
  const [feedback, setFeedback] = useState(null);

  const counts = useMemo(() => ({
    All: users.length,
    Student: users.filter((user) => user.role === 'Student').length,
    Faculty: users.filter((user) => user.role === 'Faculty').length,
  }), [users]);

  const visibleUsers = filter === 'All' ? users : users.filter((user) => user.role === filter);

  const handleDecision = (user, decision) => {
    setUsers((currentUsers) => currentUsers.filter((item) => item.id !== user.id));
    setFeedback({
      variant: decision === 'approved' ? 'success' : 'warning',
      message: `${user.name} was ${decision}.`,
    });
  };

  return (
    <Layout title="Manage Users" subtitle="Approve or reject pending account registration requests">
      {feedback && (
        <StatusAlert
          variant={feedback.variant}
          message={feedback.message}
          onDismiss={() => setFeedback(null)}
        />
      )}

      <div className="admin-filter-tabs mb-4">
        {['All', 'Student', 'Faculty'].map((item) => (
          <button
            key={item}
            type="button"
            className={filter === item ? 'is-active' : ''}
            onClick={() => setFilter(item)}
          >
            {item} ({counts[item]})
          </button>
        ))}
      </div>

      <section className="faculty-panel p-0 overflow-hidden">
        <div className="table-responsive">
          <table className="table faculty-table admin-user-table align-middle mb-0">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Department</th>
                <th>Applied On</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {visibleUsers.map((user) => (
                <tr key={user.id}>
                  <td>
                    <div className="admin-user-cell">
                      <span>{user.name.charAt(0)}</span>
                      <strong>{user.name}</strong>
                    </div>
                  </td>
                  <td>{user.email}</td>
                  <td><span className={`course-pill ${user.role === 'Faculty' ? 'course-pill-violet' : 'course-pill-primary'}`}>{user.role}</span></td>
                  <td>{user.department}</td>
                  <td>{user.appliedOn}</td>
                  <td>
                    <div className="d-flex flex-wrap gap-2">
                      <button type="button" className="btn btn-sm btn-success" onClick={() => handleDecision(user, 'approved')}>Approve</button>
                      <button type="button" className="btn btn-sm btn-outline-danger" onClick={() => handleDecision(user, 'rejected')}>Reject</button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>
    </Layout>
  );
}

export default ManageUsersPage;