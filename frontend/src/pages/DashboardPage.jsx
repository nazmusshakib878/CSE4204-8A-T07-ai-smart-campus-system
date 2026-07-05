import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import Layout from '../components/Layout';
import { useAuth } from '../auth/auth-context';
import { deleteTask, getTasks, getRecommendations, getLearningResources } from '../services/api';
import { ConfirmDialog, EmptyState, LoadingState, StatusAlert } from '../components/Feedback';

function DashboardPage() {
  const { user: profile } = useAuth();
  const [tasks, setTasks] = useState([]);
  const [recommendations, setRecommendations] = useState([]);
  const [resources, setResources] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [feedback, setFeedback] = useState(null);
  const [taskToDelete, setTaskToDelete] = useState(null);
  const [deletingTask, setDeletingTask] = useState(false);

  const fetchData = useCallback(async () => {
    setLoading(true);
    setError('');

    try {
      const [tasksRes, recommendationsRes, resourcesRes] = await Promise.all([
        getTasks(),
        getRecommendations(),
        getLearningResources()
      ]);

      setTasks(tasksRes.data.data || []);
      setRecommendations(recommendationsRes.data.data || []);
      setResources(resourcesRes.data.data || []);
    } catch (requestError) {
      setError(requestError.message || 'Dashboard data could not be loaded.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  const handleDeleteTask = async () => {
    if (!taskToDelete) return;

    setDeletingTask(true);
    setFeedback(null);

    try {
      await deleteTask(taskToDelete.id);
      setTasks((currentTasks) => (
        currentTasks.filter((task) => task.id !== taskToDelete.id)
      ));
      setFeedback({
        variant: 'success',
        message: `"${taskToDelete.title}" was deleted successfully.`,
      });
      setTaskToDelete(null);
    } catch (requestError) {
      setFeedback({
        variant: 'danger',
        message: requestError.message || 'The task could not be deleted. Please try again.',
      });
      setTaskToDelete(null);
    } finally {
      setDeletingTask(false);
    }
  };

  if (loading) {
    return (
      <Layout title="Dashboard" subtitle="Preparing your personalized overview...">
        <LoadingState message="Loading dashboard data..." />
      </Layout>
    );
  }

  return (
    <>
      <Layout
        title={`Welcome back, ${profile?.name?.split(' ')[0] || 'Student'}`}
        subtitle="Here’s a clear view of your campus activity today."
      >
        {error && (
          <StatusAlert
            variant="danger"
            message={error}
            actionLabel="Try again"
            onAction={fetchData}
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

        <div className="row g-4 mb-4">
          <div className="col-md-6 col-xl-3">
            <div className="dashboard-stat-card stat-blue card border-0 shadow-sm rounded-4 p-4 h-100">
              <p className="text-muted small mb-1">Academic role</p>
              <h3 className="fw-bold text-dark text-capitalize mb-1">{profile?.role || 'Student'}</h3>
              <small className="text-secondary">Authenticated account</small>
            </div>
          </div>
          <div className="col-md-6 col-xl-3">
            <div className="dashboard-stat-card stat-green card border-0 shadow-sm rounded-4 p-4 h-100">
              <p className="text-muted small mb-1">Active tasks</p>
              <h3 className="fw-bold text-dark mb-1">{tasks.length}</h3>
              <small className="text-secondary">Available tasks</small>
            </div>
          </div>
          <div className="col-md-6 col-xl-3">
            <div className="dashboard-stat-card stat-violet card border-0 shadow-sm rounded-4 p-4 h-100">
              <p className="text-muted small mb-1">Smart insights</p>
              <h3 className="fw-bold text-dark mb-1">{recommendations.length}</h3>
              <small className="text-secondary">AI suggestions</small>
            </div>
          </div>
          <div className="col-md-6 col-xl-3">
            <div className="dashboard-stat-card stat-amber card border-0 shadow-sm rounded-4 p-4 h-100">
              <p className="text-muted small mb-1">Learning resources</p>
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
              {recommendations.length > 0 ? (
                <div className="list-group list-group-flush">
                  {recommendations.slice(0, 3).map((item) => (
                    <div key={item.id} className="list-group-item px-0 py-3">
                      <div className="fw-semibold text-dark">{item.title}</div>
                      <small className="text-secondary">{item.description}</small>
                    </div>
                  ))}
                </div>
              ) : (
                <EmptyState
                  title={error ? 'Recommendations unavailable' : 'No recommendations yet'}
                  message={error
                    ? 'Retry the dashboard request to load your recommendations.'
                    : 'Personalized suggestions will appear here when they become available.'}
                />
              )}
            </div>
          </div>

          <div className="col-lg-4">
            <div className="card border-0 shadow-sm rounded-4 p-4 h-100">
              <h5 className="fw-bold text-dark mb-3">Upcoming tasks</h5>
              {tasks.length > 0 ? (
                <div className="d-grid gap-3">
                  {tasks.slice(0, 3).map((task) => (
                    <div key={task.id} className="task-item rounded-3 bg-light p-3 d-flex align-items-center justify-content-between gap-3">
                      <div className="min-w-0">
                        <div className="fw-semibold text-dark">{task.title}</div>
                        <small className="text-secondary">{task.due_date || task.status}</small>
                      </div>
                      <button
                        type="button"
                        className="btn btn-sm btn-outline-danger rounded-pill px-3 flex-shrink-0"
                        aria-label={`Delete ${task.title}`}
                        onClick={() => setTaskToDelete(task)}
                      >
                        Delete
                      </button>
                    </div>
                  ))}
                </div>
              ) : (
                <EmptyState
                  title={error ? 'Tasks unavailable' : 'You’re all caught up'}
                  message={error
                    ? 'Retry the dashboard request to load your tasks.'
                    : 'There are no upcoming tasks to show.'}
                />
              )}
            </div>
          </div>
        </div>
      </Layout>

      <ConfirmDialog
        open={Boolean(taskToDelete)}
        title="Delete this task?"
        message={taskToDelete
          ? `"${taskToDelete.title}" will be permanently removed. This action cannot be undone.`
          : ''}
        confirmLabel="Delete task"
        loading={deletingTask}
        onConfirm={handleDeleteTask}
        onCancel={() => setTaskToDelete(null)}
      />
    </>
  );
}

export default DashboardPage;
