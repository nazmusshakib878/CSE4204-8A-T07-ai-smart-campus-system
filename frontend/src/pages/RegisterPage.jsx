import { useEffect, useState } from 'react';
import { Link, Navigate, useNavigate } from 'react-router-dom';
import Layout from '../components/Layout';
import AuthPageLayout from '../components/AuthPageLayout';
import { useAuth } from '../auth/auth-context';
import { getDepartments } from '../services/api';
import { validateRegistrationForm } from '../utils/validation';
import { getDashboardPath } from '../utils/routes';
import { StatusAlert } from '../components/Feedback';

const FALLBACK_DEPARTMENTS = [
  'Computer Science & Engineering',
  'Electrical & Electronic Engineering',
  'Mechanical Engineering',
  'Civil Engineering',
  'Chemical Engineering',
];

function RegisterPage() {
  const navigate = useNavigate();
  const { isAuthenticated, user, register } = useAuth();
  const [departments, setDepartments] = useState(FALLBACK_DEPARTMENTS);
  const [form, setForm] = useState({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    role: 'student',
    department: '',
    student_id: '',
    faculty_id: '',
  });
  const [errors, setErrors] = useState({});
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    let active = true;

    getDepartments()
      .then((response) => {
        if (!active) return;
        const names = (response.data.data || []).map((department) => department.name).filter(Boolean);
        if (names.length > 0) setDepartments(names);
      })
      .catch(() => {
        if (active) setDepartments(FALLBACK_DEPARTMENTS);
      });

    return () => {
      active = false;
    };
  }, []);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setForm((current) => ({ ...current, [name]: value }));
    setErrors((current) => {
      if (!current[name] && !(name === 'password' && current.password_confirmation)) {
        return current;
      }

      const next = { ...current };
      delete next[name];
      if (name === 'password') delete next.password_confirmation;
      if (name === 'role') {
        delete next.student_id;
        delete next.faculty_id;
      }
      return next;
    });
    setError('');
  };

  const handleBlur = (e) => {
    const { name } = e.target;
    const fieldError = validateRegistrationForm(form)[name];

    if (fieldError) {
      setErrors((current) => ({ ...current, [name]: fieldError }));
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const normalizedForm = {
      ...form,
      name: form.name.trim(),
      email: form.email.trim().toLowerCase(),
      phone: form.phone.trim(),
      department: form.department.trim(),
      student_id: form.student_id.trim().toUpperCase(),
      faculty_id: form.faculty_id.trim().toUpperCase(),
    };
    const validationErrors = validateRegistrationForm(normalizedForm);

    if (Object.keys(validationErrors).length > 0) {
      setErrors(validationErrors);
      setError('Please correct the highlighted fields before creating your account.');
      return;
    }

    setForm(normalizedForm);
    setErrors({});
    setError('');
    setLoading(true);

    try {
      await register(normalizedForm);
      navigate('/login', {
        replace: true,
        state: {
          flash: {
            variant: 'success',
            message: 'Registration submitted successfully. Your account is pending administrator approval.',
          },
        },
      });
    } catch (err) {
      if (Object.keys(err.fields || {}).length > 0) {
        setErrors(err.fields);
      }
      setError(err.message || 'Registration failed.');
    } finally {
      setLoading(false);
    }
  };

  if (isAuthenticated) {
    return <Navigate to={getDashboardPath(user)} replace />;
  }

  return (
    <Layout>
      <AuthPageLayout
        eyebrow="JOIN THE CAMPUS"
        title="Create your account"
        subtitle="Set up your profile once, then access every academic tool after approval."
      >
        <div className="auth-form-card">
          {error && (
            <StatusAlert
              variant="danger"
              message={error}
              onDismiss={() => setError('')}
            />
          )}

          <form onSubmit={handleSubmit} noValidate>
            <div className="row g-3">
              <div className="col-md-6">
                <label className="form-label" htmlFor="register-name">Full name</label>
                <input
                  id="register-name"
                  name="name"
                  autoComplete="name"
                  className={`form-control${errors.name ? ' is-invalid' : ''}`}
                  value={form.name}
                  onChange={handleChange}
                  onBlur={handleBlur}
                  placeholder="Ayesha Rahman"
                  aria-invalid={Boolean(errors.name)}
                  aria-describedby={errors.name ? 'register-name-error' : undefined}
                  required
                />
                {errors.name && <div id="register-name-error" className="invalid-feedback">{errors.name}</div>}
              </div>
              <div className="col-md-6">
                <label className="form-label" htmlFor="register-email">Email</label>
                <input
                  id="register-email"
                  type="email"
                  name="email"
                  autoComplete="email"
                  className={`form-control${errors.email ? ' is-invalid' : ''}`}
                  value={form.email}
                  onChange={handleChange}
                  onBlur={handleBlur}
                  placeholder="you@example.com"
                  aria-invalid={Boolean(errors.email)}
                  aria-describedby={errors.email ? 'register-email-error' : undefined}
                  required
                />
                {errors.email && <div id="register-email-error" className="invalid-feedback">{errors.email}</div>}
              </div>
              <div className="col-md-6">
                <label className="form-label" htmlFor="register-phone">Phone number</label>
                <input
                  id="register-phone"
                  name="phone"
                  type="tel"
                  autoComplete="tel"
                  className={`form-control${errors.phone ? ' is-invalid' : ''}`}
                  value={form.phone}
                  onChange={handleChange}
                  onBlur={handleBlur}
                  placeholder="01712345678"
                  aria-invalid={Boolean(errors.phone)}
                  aria-describedby={errors.phone ? 'register-phone-error' : undefined}
                  required
                />
                {errors.phone && <div id="register-phone-error" className="invalid-feedback">{errors.phone}</div>}
              </div>
              <div className="col-md-6">
                <label className="form-label" htmlFor="register-role">Role</label>
                <select
                  id="register-role"
                  name="role"
                  className={`form-select${errors.role ? ' is-invalid' : ''}`}
                  value={form.role}
                  onChange={handleChange}
                  onBlur={handleBlur}
                  aria-invalid={Boolean(errors.role)}
                  aria-describedby={errors.role ? 'register-role-error' : undefined}
                  required
                >
                  <option value="student">Student</option>
                  <option value="faculty">Faculty</option>
                </select>
                {errors.role && <div id="register-role-error" className="invalid-feedback">{errors.role}</div>}
              </div>
              <div className="col-md-6">
                <label className="form-label" htmlFor="register-department">Department</label>
                <select
                  id="register-department"
                  name="department"
                  className={`form-select${errors.department ? ' is-invalid' : ''}`}
                  value={form.department}
                  onChange={handleChange}
                  onBlur={handleBlur}
                  aria-invalid={Boolean(errors.department)}
                  aria-describedby={errors.department ? 'register-department-error' : undefined}
                  required
                >
                  <option value="">Select department</option>
                  {departments.map((department) => (
                    <option key={department} value={department}>{department}</option>
                  ))}
                </select>
                {errors.department && <div id="register-department-error" className="invalid-feedback">{errors.department}</div>}
              </div>
              {form.role === 'student' ? (
                <div className="col-md-6">
                  <label className="form-label" htmlFor="register-student-id">Student ID</label>
                  <input
                    id="register-student-id"
                    name="student_id"
                    className={`form-control${errors.student_id ? ' is-invalid' : ''}`}
                    value={form.student_id}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    placeholder="CE66334459156"
                    aria-invalid={Boolean(errors.student_id)}
                    aria-describedby={errors.student_id ? 'register-student-id-error' : undefined}
                    required
                  />
                  {errors.student_id && <div id="register-student-id-error" className="invalid-feedback">{errors.student_id}</div>}
                </div>
              ) : (
                <div className="col-md-6">
                  <label className="form-label" htmlFor="register-faculty-id">Faculty ID</label>
                  <input
                    id="register-faculty-id"
                    name="faculty_id"
                    className={`form-control${errors.faculty_id ? ' is-invalid' : ''}`}
                    value={form.faculty_id}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    placeholder="FAC-CE-0045"
                    aria-invalid={Boolean(errors.faculty_id)}
                    aria-describedby={errors.faculty_id ? 'register-faculty-id-error' : undefined}
                    required
                  />
                  {errors.faculty_id && <div id="register-faculty-id-error" className="invalid-feedback">{errors.faculty_id}</div>}
                </div>
              )}
              <div className="col-md-6">
                <label className="form-label" htmlFor="register-password">Password</label>
                <input
                  id="register-password"
                  type="password"
                  name="password"
                  autoComplete="new-password"
                  className={`form-control${errors.password ? ' is-invalid' : ''}`}
                  value={form.password}
                  onChange={handleChange}
                  onBlur={handleBlur}
                  placeholder="Password"
                  aria-invalid={Boolean(errors.password)}
                  aria-describedby={errors.password
                    ? 'register-password-help register-password-error'
                    : 'register-password-help'}
                  required
                />
                {errors.password && <div id="register-password-error" className="invalid-feedback">{errors.password}</div>}
                <div id="register-password-help" className="form-text">
                  Use 8+ characters with uppercase, lowercase, a number, and a symbol.
                </div>
              </div>
              <div className="col-md-6">
                <label className="form-label" htmlFor="register-password-confirmation">Confirm password</label>
                <input
                  id="register-password-confirmation"
                  type="password"
                  name="password_confirmation"
                  autoComplete="new-password"
                  className={`form-control${errors.password_confirmation ? ' is-invalid' : ''}`}
                  value={form.password_confirmation}
                  onChange={handleChange}
                  onBlur={handleBlur}
                  placeholder="Confirm password"
                  aria-invalid={Boolean(errors.password_confirmation)}
                  aria-describedby={errors.password_confirmation ? 'register-password-confirmation-error' : undefined}
                  required
                />
                {errors.password_confirmation && (
                  <div id="register-password-confirmation-error" className="invalid-feedback">
                    {errors.password_confirmation}
                  </div>
                )}
              </div>
            </div>

            <button type="submit" className="btn btn-primary rounded-3 w-100 py-2 mt-4" disabled={loading} aria-busy={loading}>
              {loading && <span className="spinner-border spinner-border-sm me-2" aria-hidden="true" />}
              {loading ? 'Submitting registration...' : 'Create my account'}
            </button>
          </form>

          <p className="text-center text-secondary mt-4 mb-0">
            Already have an account? <Link to="/login" className="fw-semibold text-primary">Sign in</Link>
          </p>
        </div>
      </AuthPageLayout>
    </Layout>
  );
}

export default RegisterPage;