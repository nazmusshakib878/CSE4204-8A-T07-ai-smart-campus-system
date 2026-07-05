import { useCallback, useEffect, useMemo, useState } from 'react';
import Layout from '../components/Layout';
import { ConfirmDialog, EmptyState, LoadingState, StatusAlert } from '../components/Feedback';
import { createTask, deleteTask, getLearningResources, getTasks } from '../services/api';

const toolCards = [
  {
    key: 'tasks',
    title: 'Campus Tasks',
    desc: 'Create, review, and remove daily academic or administrative tasks.',
    badge: 'Live',
    icon: 'CT',
    tone: 'blue',
  },
  {
    key: 'resources',
    title: 'Learning Resources',
    desc: 'Access notes, references, study materials, and helpful links.',
    badge: 'Live',
    icon: 'LR',
    tone: 'violet',
  },
  {
    key: 'attendance',
    title: 'Attendance Records',
    desc: 'Monitor daily presence, class participation, and student consistency.',
    badge: 'Soon',
    icon: 'AR',
    tone: 'green',
  },
  {
    key: 'gradebook',
    title: 'Gradebook',
    desc: 'Review marks, assignments, exam progress, and semester performance.',
    badge: 'Soon',
    icon: 'GB',
    tone: 'amber',
  },
];

const emptyTaskForm = {
  title: '',
  description: '',
  assigned_to: '',
  due_date: '',
  status: 'pending',
  priority: 'medium',
};

function FunctionsPage() {
  const [activeTool, setActiveTool] = useState('tasks');
  const [tasks, setTasks] = useState([]);
  const [resources, setResources] = useState([]);
  const [loading, setLoading] = useState(true);
  const [savingTask, setSavingTask] = useState(false);
  const [deletingTask, setDeletingTask] = useState(false);
  const [taskToDelete, setTaskToDelete] = useState(null);
  const [taskForm, setTaskForm] = useState(emptyTaskForm);
  const [error, setError] = useState('');
  const [feedback, setFeedback] = useState(null);

  const fetchToolsData = useCallback(async () => {
    setLoading(true);
    setError('');

    try {
      const [tasksRes, resourcesRes] = await Promise.all([
        getTasks(),
        getLearningResources(),
      ]);

      setTasks(tasksRes.data.data || []);
      setResources(resourcesRes.data.data || []);
    } catch (requestError) {
      setError(requestError.message || 'Campus tools data could not be loaded.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchToolsData();
  }, [fetchToolsData]);

  const activeToolMeta = useMemo(
    () => toolCards.find((tool) => tool.key === activeTool),
    [activeTool]
  );

  const handleTaskFieldChange = (event) => {
    const { name, value } = event.target;
    setTaskForm((currentForm) => ({
      ...currentForm,
      [name]: value,
    }));
  };

  const handleCreateTask = async (event) => {
    event.preventDefault();
    setSavingTask(true);
    setFeedback(null);

    try {
      const payload = Object.fromEntries(
        Object.entries(taskForm).map(([key, value]) => [key, value.trim ? value.trim() : value])
      );

      const response = await createTask(payload);
      setTasks((currentTasks) => [response.data.data, ...currentTasks]);
      setTaskForm(emptyTaskForm);
      setFeedback({
        variant: 'success',
        message: 'Task created successfully.',
      });
    } catch (requestError) {
      setFeedback({
        variant: 'danger',
        message: requestError.message || 'The task could not be created.',
      });
    } finally {
      setSavingTask(false);
    }
  };

  const handleDeleteTask = async () => {
    if (!taskToDelete) return;

    setDeletingTask(true);
    setFeedback(null);

    try {
      await deleteTask(taskToDelete.id);
      setTasks((currentTasks) => currentTasks.filter((task) => task.id !== taskToDelete.id));
      setFeedback({
        variant: 'success',
        message: `"${taskToDelete.title}" was deleted successfully.`,
      });
      setTaskToDelete(null);
    } catch (requestError) {
      setFeedback({
        variant: 'danger',
        message: requestError.message || 'The task could not be deleted.',
      });
      setTaskToDelete(null);
    } finally {
      setDeletingTask(false);
    }
  };

  const renderTasksTool = () => (
    <div className="row g-4">
      <div className="col-xl-5">
        <form className="card border-0 shadow-sm rounded-4 p-4 h-100" onSubmit={handleCreateTask}>
          <div className="section-card-header mb-3">
            <span className="eyebrow-label">Task manager</span>
            <h5 className="fw-bold text-dark mb-0">Add campus task</h5>
          </div>

          <div className="mb-3">
            <label className="form-label fw-semibold" htmlFor="task-title">Title</label>
            <input
              id="task-title"
              name="title"
              type="text"
              className="form-control"
              value={taskForm.title}
              onChange={handleTaskFieldChange}
              maxLength={255}
              required
            />
          </div>

          <div className="mb-3">
            <label className="form-label fw-semibold" htmlFor="task-description">Description</label>
            <textarea
              id="task-description"
              name="description"
              className="form-control"
              rows="3"
              value={taskForm.description}
              onChange={handleTaskFieldChange}
            />
          </div>

          <div className="row g-3">
            <div className="col-md-6">
              <label className="form-label fw-semibold" htmlFor="task-assigned-to">Assigned to</label>
              <input
                id="task-assigned-to"
                name="assigned_to"
                type="text"
                className="form-control"
                value={taskForm.assigned_to}
                onChange={handleTaskFieldChange}
                maxLength={255}
              />
            </div>
            <div className="col-md-6">
              <label className="form-label fw-semibold" htmlFor="task-due-date">Due date</label>
              <input
                id="task-due-date"
                name="due_date"
                type="date"
                className="form-control"
                value={taskForm.due_date}
                onChange={handleTaskFieldChange}
              />
            </div>
            <div className="col-md-6">
              <label className="form-label fw-semibold" htmlFor="task-status">Status</label>
              <select
                id="task-status"
                name="status"
                className="form-select"
                value={taskForm.status}
                onChange={handleTaskFieldChange}
              >
                <option value="pending">Pending</option>
                <option value="in_progress">In progress</option>
                <option value="completed">Completed</option>
              </select>
            </div>
            <div className="col-md-6">
              <label className="form-label fw-semibold" htmlFor="task-priority">Priority</label>
              <select
                id="task-priority"
                name="priority"
                className="form-select"
                value={taskForm.priority}
                onChange={handleTaskFieldChange}
              >
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
              </select>
            </div>
          </div>

          <button type="submit" className="btn btn-primary w-100 mt-4" disabled={savingTask} aria-busy={savingTask}>
            {savingTask && <span className="spinner-border spinner-border-sm me-2" aria-hidden="true" />}
            {savingTask ? 'Saving task...' : 'Create task'}
          </button>
        </form>
      </div>

      <div className="col-xl-7">
        <div className="card border-0 shadow-sm rounded-4 p-4 h-100">
          <div className="section-card-header d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
              <span className="eyebrow-label">Active work</span>
              <h5 className="fw-bold text-dark mb-0">Campus tasks</h5>
            </div>
            <span className="badge rounded-pill text-bg-primary">{tasks.length} total</span>
          </div>

          {tasks.length > 0 ? (
            <div className="campus-tool-list">
              {tasks.map((task) => (
                <article key={task.id} className="campus-tool-list-item">
                  <div className="min-w-0">
                    <div className="d-flex flex-wrap align-items-center gap-2 mb-1">
                      <h6 className="fw-bold text-dark mb-0">{task.title}</h6>
                      {task.priority && (
                        <span className={`tool-chip tool-chip-${task.priority}`}>{task.priority}</span>
                      )}
                    </div>
                    {task.description && <p className="text-secondary mb-2">{task.description}</p>}
                    <div className="d-flex flex-wrap gap-2 text-secondary small">
                      <span>Status: {String(task.status || 'pending').replace('_', ' ')}</span>
                      {task.assigned_to && <span>Assigned: {task.assigned_to}</span>}
                      {task.due_date && <span>Due: {task.due_date}</span>}
                    </div>
                  </div>
                  <button
                    type="button"
                    className="btn btn-sm btn-outline-danger rounded-pill px-3 flex-shrink-0"
                    onClick={() => setTaskToDelete(task)}
                  >
                    Delete
                  </button>
                </article>
              ))}
            </div>
          ) : (
            <EmptyState
              title="No tasks yet"
              message="Create a task from the form and it will appear here."
            />
          )}
        </div>
      </div>
    </div>
  );

  const renderResourcesTool = () => (
    <div className="card border-0 shadow-sm rounded-4 p-4">
      <div className="section-card-header d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
          <span className="eyebrow-label">Study materials</span>
          <h5 className="fw-bold text-dark mb-0">Learning resources</h5>
        </div>
        <span className="badge rounded-pill text-bg-primary">{resources.length} resources</span>
      </div>

      {resources.length > 0 ? (
        <div className="row g-3">
          {resources.map((resource) => (
            <div key={resource.id} className="col-md-6 col-xl-4">
              <article className="resource-card h-100">
                <div className="d-flex align-items-center justify-content-between gap-2 mb-2">
                  <span className="tool-chip tool-chip-medium">{resource.category}</span>
                  <small className="text-secondary">{resource.resource_type}</small>
                </div>
                <h6 className="fw-bold text-dark">{resource.title}</h6>
                {resource.description && <p className="text-secondary small">{resource.description}</p>}
                {resource.uploaded_by && (
                  <small className="d-block text-secondary mb-3">Uploaded by {resource.uploaded_by}</small>
                )}
                {resource.resource_url ? (
                  <a
                    className="btn btn-outline-primary w-100"
                    href={resource.resource_url}
                    target="_blank"
                    rel="noreferrer"
                  >
                    Open resource
                  </a>
                ) : (
                  <button type="button" className="btn btn-outline-secondary w-100" disabled>
                    No link attached
                  </button>
                )}
              </article>
            </div>
          ))}
        </div>
      ) : (
        <EmptyState
          title="No resources found"
          message="Learning materials will appear here when they are added from the API."
        />
      )}
    </div>
  );

  const renderComingSoonTool = () => (
    <div className="card border-0 shadow-sm rounded-4 p-4">
      <EmptyState
        title={`${activeToolMeta?.title || 'This tool'} is not connected yet`}
        message="The interface is ready, but this project does not have a backend endpoint for this tool yet."
      />
    </div>
  );

  const renderActiveTool = () => {
    if (activeTool === 'tasks') return renderTasksTool();
    if (activeTool === 'resources') return renderResourcesTool();
    return renderComingSoonTool();
  };

  return (
    <>
      <Layout title="Campus Tools" subtitle="Explore the platform's core academic and administrative tools.">
        <div className="tool-banner mb-4">
          <div>
            <span className="eyebrow-label">Workspace</span>
            <h3>Everything for daily campus work</h3>
            <p>Manage tasks, open resources, and review available academic tools from one clean area.</p>
          </div>
          <span className="tool-banner-count">{toolCards.length} tools</span>
        </div>

        {error && (
          <StatusAlert
            variant="danger"
            message={error}
            actionLabel="Try again"
            onAction={fetchToolsData}
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
          {toolCards.map((card) => (
            <div key={card.key} className="col-md-6 col-xl-3">
              <button
                type="button"
                className={`tool-card tool-card-${card.tone} tool-selector-card h-100 w-100 text-start${activeTool === card.key ? ' is-active' : ''}`}
                onClick={() => setActiveTool(card.key)}
                aria-pressed={activeTool === card.key}
              >
                <div className="tool-icon">{card.icon}</div>
                <div className="section-card-header d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                  <h5 className="fw-bold text-dark mb-0">{card.title}</h5>
                  <span className="badge rounded-pill bg-white text-primary">{card.badge}</span>
                </div>
                <p className="text-secondary mb-0">{card.desc}</p>
              </button>
            </div>
          ))}
        </div>

        {loading ? <LoadingState message="Loading campus tools..." /> : renderActiveTool()}
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

export default FunctionsPage;