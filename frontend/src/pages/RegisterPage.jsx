import { useState } from 'react';
import { Link, Navigate, useNavigate } from 'react-router-dom';
import Layout from '../components/Layout';
import AuthPageLayout from '../components/AuthPageLayout';
import { useAuth } from '../auth/auth-context';
import { validateRegistrationForm } from '../utils/validation';
import { getDashboardPath } from '../utils/routes';
import { StatusAlert } from '../components/Feedback';

function RegisterPage() {
  const navigate = useNavigate();
  const { isAuthenticated, user, register } = useAuth();
  const [form, setForm] = useState({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'student'
  });
  const [errors, setErrors] = useState({});
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

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
      const registeredUser = await register(normalizedForm);
      navigate(getDashboardPath(registeredUser), {
        replace: true,
        state: {
          flash: {
            variant: 'success',
            message: `Welcome, ${registeredUser.name}. Your account is ready.`,
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
        subtitle="Set up your profile once, then access every academic tool from one place."
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
                    <option value="admin">Admin</option>
                  </select>
                  {errors.role && <div id="register-role-error" className="invalid-feedback">{errors.role}</div>}
              </div>
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
                    placeholder="••••••••"
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
                    placeholder="••••••••"
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
              {loading ? 'Creating account...' : 'Create my account'}
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
