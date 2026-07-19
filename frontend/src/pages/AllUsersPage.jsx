import { useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import Layout from '../components/Layout';
import { EmptyState, LoadingState, StatusAlert } from '../components/Feedback';
import { getAllUsers } from '../services/api';

const departments = ['CSE', 'EEE', 'BBA', 'English', 'Civil', 'Other'];
const departmentLabels = { CSE: 'CSE', EEE: 'EEE', BBA: 'BBA', English: 'English', Civil: 'Civil', Other: 'Other Departments' };
const semesters = Array.from({ length: 8 }, (_, index) => index + 1);
const sections = ['A', 'B', 'C'];

const universityId = (user) => user.university_id || 'Not assigned';
const roleLabel = (role) => (role === 'faculty' ? 'Faculty' : 'Student');

function TreeButton({ active, children, onClick, compact = false }) {
  return (
    <button
      type="button"
      className={`all-users-tree-button${active ? ' is-active' : ''}${compact ? ' is-compact' : ''}`}
      onClick={onClick}
    >
      {children}
    </button>
  );
}

function AllUsersPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [feedback, setFeedback] = useState(null);

  const filters = useMemo(() => ({
    role: searchParams.get('role') || '',
    department: searchParams.get('department') || '',
    semester: searchParams.get('semester') || '',
    section: searchParams.get('section') || '',
  }), [searchParams]);

  useEffect(() => {
    let active = true;
    setLoading(true);
    setFeedback(null);

    getAllUsers(Object.fromEntries(Object.entries(filters).filter(([, value]) => value)))
      .then((response) => {
        if (active) setUsers(response.data.data || []);
      })
      .catch((error) => {
        if (active) setFeedback({ variant: 'danger', message: error.message || 'Users could not be loaded.' });
      })
      .finally(() => {
        if (active) setLoading(false);
      });

    return () => {
      active = false;
    };
  }, [filters]);

  const selectFilter = (nextFilters = {}) => {
    const normalized = Object.fromEntries(
      Object.entries(nextFilters).filter(([, value]) => value !== '' && value !== null && value !== undefined),
    );
    setSearchParams(normalized);
  };

  const contextLabel = [
    filters.role ? roleLabel(filters.role) : 'All Users',
    filters.department ? departmentLabels[filters.department] : null,
    filters.semester ? `Semester ${filters.semester}` : null,
    filters.section ? `Section ${filters.section}` : null,
  ].filter(Boolean).join(' / ');

  return (
    <Layout title="All Users" subtitle="Browse approved faculty and students by department, semester, and section">
      {feedback && <StatusAlert variant={feedback.variant} message={feedback.message} onDismiss={() => setFeedback(null)} />}

      <div className="all-users-layout">
        <aside className="all-users-tree faculty-panel" aria-label="All users hierarchy">
          <div className="all-users-tree-heading">
            <span className="eyebrow-label">Directory</span>
            <h5>All Users</h5>
          </div>

          <TreeButton active={!filters.role} onClick={() => selectFilter()}>All Users</TreeButton>

          <details open={filters.role === 'faculty'}>
            <summary>Faculty</summary>
            <div className="all-users-tree-branch">
              {departments.map((department) => (
                <TreeButton
                  key={`faculty-${department}`}
                  active={filters.role === 'faculty' && filters.department === department}
                  onClick={() => selectFilter({ role: 'faculty', department })}
                >
                  {departmentLabels[department]}
                </TreeButton>
              ))}
            </div>
          </details>

          <details open={filters.role === 'student'}>
            <summary>Students</summary>
            <div className="all-users-tree-branch">
              {departments.map((department) => (
                <details key={`student-${department}`} open={filters.department === department}>
                  <summary>{departmentLabels[department]}</summary>
                  <div className="all-users-tree-branch">
                    {semesters.map((semester) => (
                      <details key={`${department}-${semester}`} open={filters.department === department && filters.semester === String(semester)}>
                        <summary>{semester}</summary>
                        <div className="all-users-section-grid">
                          <TreeButton
                            compact
                            active={filters.role === 'student' && filters.department === department && filters.semester === String(semester) && !filters.section}
                            onClick={() => selectFilter({ role: 'student', department, semester })}
                          >
                            All
                          </TreeButton>
                          {sections.map((section) => (
                            <TreeButton
                              compact
                              key={`${department}-${semester}-${section}`}
                              active={filters.role === 'student' && filters.department === department && filters.semester === String(semester) && filters.section === section}
                              onClick={() => selectFilter({ role: 'student', department, semester, section })}
                            >
                              {semester}{section}
                            </TreeButton>
                          ))}
                        </div>
                      </details>
                    ))}
                  </div>
                </details>
              ))}
            </div>
          </details>
        </aside>

        <section className="all-users-results">
          <div className="faculty-panel all-users-summary">
            <div>
              <span className="eyebrow-label">Current view</span>
              <h4>{contextLabel}</h4>
              <p>Only approved accounts are shown. Pending registrations remain in Manage Users.</p>
            </div>
            <span className="all-users-count">{users.length} user{users.length === 1 ? '' : 's'}</span>
          </div>

          {loading ? (
            <LoadingState message="Loading users..." />
          ) : users.length > 0 ? (
            <div className="faculty-panel p-0 overflow-hidden">
              <div className="table-responsive">
                <table className="table faculty-table align-middle mb-0">
                  <thead>
                    <tr>
                      <th>User</th>
                      <th>University ID</th>
                      <th>Role</th>
                      <th>Department</th>
                      <th>Semester</th>
                      <th>Section</th>
                      <th>Phone</th>
                    </tr>
                  </thead>
                  <tbody>
                    {users.map((user) => (
                      <tr key={user.id}>
                        <td>
                          <div className="admin-user-cell">
                            <span>{user.name.charAt(0).toUpperCase()}</span>
                            <div><strong>{user.name}</strong><small>{user.email}</small></div>
                          </div>
                        </td>
                        <td><strong>{universityId(user)}</strong></td>
                        <td><span className={`course-pill ${user.role === 'faculty' ? 'course-pill-violet' : 'course-pill-primary'}`}>{roleLabel(user.role)}</span></td>
                        <td>{user.department || 'Not assigned'}</td>
                        <td>{user.role === 'student' ? user.semester || 'Not assigned' : '—'}</td>
                        <td>{user.role === 'student' ? user.section || 'Not assigned' : '—'}</td>
                        <td>{user.phone || '—'}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          ) : (
            <EmptyState title="No users in this group" message="Choose another department, semester, or section from the directory." />
          )}
        </section>
      </div>
    </Layout>
  );
}

export default AllUsersPage;
