import { useCallback, useEffect, useState } from 'react';
import Layout from '../components/Layout';
import { EmptyState, LoadingState, StatusAlert } from '../components/Feedback';
import { createDepartment, deleteDepartment, getAdminDepartments, updateDepartmentStatus } from '../services/api';

const emptyForm = { name: '', code: '' };

function ManageDepartmentsPage() {
  const [departments, setDepartments] = useState([]);
  const [form, setForm] = useState(emptyForm);
  const [errors, setErrors] = useState({});
  const [feedback, setFeedback] = useState(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [busyId, setBusyId] = useState(null);

  const fetchDepartments = useCallback(async () => {
    setLoading(true);
    setFeedback(null);

    try {
      const response = await getAdminDepartments();
      setDepartments(response.data.data || []);
    } catch (error) {
      setFeedback({ variant: 'danger', message: error.message || 'Departments could not be loaded.' });
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchDepartments();
  }, [fetchDepartments]);

  const handleChange = (event) => {
    const { name, value } = event.target;
    setForm((current) => ({ ...current, [name]: name === 'code' ? value.toUpperCase() : value }));
    setErrors((current) => {
      if (!current[name]) return current;
      const next = { ...current };
      delete next[name];
      return next;
    });
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    const payload = {
      name: form.name.trim(),
      code: form.code.trim().toUpperCase(),
    };

    setSaving(true);
    setErrors({});
    setFeedback(null);

    try {
      const response = await createDepartment(payload);
      setDepartments((current) => [...current, response.data.data].sort((a, b) => a.name.localeCompare(b.name)));
      setForm(emptyForm);
      setFeedback({ variant: 'success', message: 'Department added successfully.' });
    } catch (error) {
      if (Object.keys(error.fields || {}).length > 0) setErrors(error.fields);
      setFeedback({ variant: 'danger', message: error.message || 'Department could not be added.' });
    } finally {
      setSaving(false);
    }
  };

  const handleStatus = async (department) => {
    setBusyId(department.id); setFeedback(null);
    try {
      const response = await updateDepartmentStatus(department.id, !department.is_active);
      setDepartments((current) => current.map((item) => item.id === department.id ? response.data.data : item));
      setFeedback({ variant: 'success', message: response.data.message });
    } catch (error) { setFeedback({ variant: 'danger', message: error.message }); }
    finally { setBusyId(null); }
  };

  const handleDelete = async (department) => {
    if (!window.confirm('Permanently delete this unused department?')) return;
    setBusyId(department.id);
    setFeedback(null);

    try {
      await deleteDepartment(department.id);
      setDepartments((current) => current.filter((item) => item.id !== department.id));
      setFeedback({ variant: 'warning', message: `${department.name} was deleted.` });
    } catch (error) {
      setFeedback({ variant: 'danger', message: error.message || 'Department could not be deleted.' });
    } finally {
      setBusyId(null);
    }
  };

  return (
    <Layout title="Manage Departments" subtitle="Control department options shown during registration">
      {feedback && (
        <StatusAlert
          variant={feedback.variant}
          message={feedback.message}
          onDismiss={() => setFeedback(null)}
        />
      )}

      <form className="faculty-panel mb-4" onSubmit={handleSubmit} noValidate>
        <div className="section-card-header mb-3">
          <span className="eyebrow-label">Department control</span>
          <h5 className="fw-bold text-dark mb-0">Add Department</h5>
        </div>
        <div className="row g-3 align-items-end">
          <div className="col-md-7">
            <label className="form-label fw-semibold" htmlFor="department-name">Department name</label>
            <input
              id="department-name"
              name="name"
              className={`form-control${errors.name ? ' is-invalid' : ''}`}
              value={form.name}
              onChange={handleChange}
              placeholder="Textile Engineering"
              required
            />
            {errors.name && <div className="invalid-feedback">{errors.name}</div>}
          </div>
          <div className="col-md-3">
            <label className="form-label fw-semibold" htmlFor="department-code">Code</label>
            <input
              id="department-code"
              name="code"
              className={`form-control${errors.code ? ' is-invalid' : ''}`}
              value={form.code}
              onChange={handleChange}
              placeholder="TE"
              required
            />
            {errors.code && <div className="invalid-feedback">{errors.code}</div>}
          </div>
          <div className="col-md-2">
            <button type="submit" className="btn btn-primary w-100" disabled={saving} aria-busy={saving}>
              {saving ? 'Adding...' : 'Add'}
            </button>
          </div>
        </div>
      </form>

      {loading ? (
        <LoadingState message="Loading departments..." />
      ) : departments.length > 0 ? (
        <section className="faculty-panel p-0 overflow-hidden">
          <div className="table-responsive">
            <table className="table faculty-table align-middle mb-0">
              <thead>
                <tr>
                  <th>Department</th>
                  <th>Code</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {departments.map((department) => (
                  <tr key={department.id}>
                    <td><strong>{department.name}</strong></td>
                    <td>{department.code}</td>
                    <td><span className={`course-pill ${department.is_active ? "course-pill-primary" : ""}`}>{department.is_active ? "Active" : "Archived"}</span></td>
                    <td>
                      <button type="button" className={`btn btn-sm me-2 ${department.is_active ? "btn-outline-warning" : "btn-outline-success"}`} onClick={() => handleStatus(department)} disabled={busyId === department.id}>
                        {department.is_active ? 'Archive' : 'Activate'}
                      </button>
                      <button
                        type="button"
                        className="btn btn-sm btn-outline-danger"
                        onClick={() => handleDelete(department)}
                        disabled={busyId === department.id}
                      >
                        {busyId === department.id ? 'Working...' : 'Delete unused'}
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </section>
      ) : (
        <EmptyState title="No departments" message="Add departments so students and faculty can select them during registration." />
      )}
    </Layout>
  );
}

export default ManageDepartmentsPage;