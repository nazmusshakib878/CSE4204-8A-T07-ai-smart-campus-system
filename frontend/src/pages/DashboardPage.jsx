import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import Layout from '../components/Layout';
import { useAuth } from '../auth/auth-context';
import { getTasks, getRecommendations, getLearningResources } from '../services/api';

function DashboardPage() {
  const { user: profile } = useAuth();
  const [tasks, setTasks] = useState([]);
  const [recommendations, setRecommendations] = useState([]);
  const [resources, setResources] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [tasksRes, recommendationsRes, resourcesRes] = await Promise.all([
          getTasks(),
          getRecommendations(),
          getLearningResources()
        ]);

        setTasks(tasksRes.data.data || []);
        setRecommendations(recommendationsRes.data.data || []);
        setResources(resourcesRes.data.data || []);
      } catch (error) {
        setError(error.message || 'Dashboard data could not be loaded.');
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  if (loading) {
    return <Layout title="Dashboard" subtitle="Loading your personalized data..."><div className="text-secondary">Loading...</div></Layout>;
  }

  return (
    <Layout title={`${profile?.role || 'Student'} Dashboard`} subtitle="Your daily academic overview and activity summary.">
      {error && <div className="alert alert-danger" role="alert">{error}</div>}
      <div className="row g-4 mb-4">
        <div className="col-md-6 col-xl-3">
          <div className="card border-0 shadow-sm rounded-4 p-4 h-100">
            <p className="text-muted small mb-1">Role</p>
            <h3 className="fw-bold text-dark mb-1">{profile?.role || 'Student'}</h3>
            <small className="text-secondary">Authenticated account</small>
          </div>
        </div>
        <div className="col-md-6 col-xl-3">
          <div className="card border-0 shadow-sm rounded-4 p-4 h-100">
            <p className="text-muted small mb-1">Tasks</p>
            <h3 className="fw-bold text-dark mb-1">{tasks.length}</h3>
            <small className="text-secondary">Available tasks</small>
          </div>
        </div>
        <div className="col-md-6 col-xl-3">
          <div className="card border-0 shadow-sm rounded-4 p-4 h-100">
            <p className="text-muted small mb-1">Recommendations</p>
            <h3 className="fw-bold text-dark mb-1">{recommendations.length}</h3>
            <small className="text-secondary">AI suggestions</small>
          </div>
        </div>
        <div className="col-md-6 col-xl-3">
          <div className="card border-0 shadow-sm rounded-4 p-4 h-100">
            <p className="text-muted small mb-1">Resources</p>
            <h3 className="fw-bold text-dark mb-1">{resources.length}</h3>
            <small className="text-secondary">Learning materials</small>
          </div>
        </div>
      </div>

      <div className="row g-4">
        <div className="col-lg-8">
          <div className="card border-0 shadow-sm rounded-4 p-4 h-100">
            <div className="section-card-header d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
              <h5 className="fw-bold text-dark mb-0">Recent activity</h5>
              <Link to="/functions" className="text-primary small">View all</Link>
            </div>
            <div className="list-group list-group-flush">
              {recommendations.slice(0, 3).map((item) => (
                <div key={item.id} className="list-group-item px-0 py-3">
                  <div className="fw-semibold text-dark">{item.title}</div>
                  <small className="text-secondary">{item.description}</small>
                </div>
              ))}
            </div>
          </div>
        </div>

        <div className="col-lg-4">
          <div className="card border-0 shadow-sm rounded-4 p-4 h-100">
            <h5 className="fw-bold text-dark mb-3">Upcoming tasks</h5>
            <div className="d-grid gap-3">
              {tasks.slice(0, 3).map((task) => (
                <div key={task.id} className="rounded-3 bg-light p-3">
                  <div className="fw-semibold text-dark">{task.title}</div>
                  <small className="text-secondary">{task.due_date || task.status}</small>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </Layout>
  );
}

export default DashboardPage;
