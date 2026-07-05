import { useState } from 'react';
import { Link, Navigate, useLocation, useNavigate } from 'react-router-dom';
import Layout from '../components/Layout';
import AuthPageLayout from '../components/AuthPageLayout';
import { useAuth } from '../auth/auth-context';
import { validateLoginForm } from '../utils/validation';
import { StatusAlert } from '../components/Feedback';

function LoginPage() {
  const navigate = useNavigate();
  const location = useLocation();
  const { isAuthenticated, login } = useAuth();
  const [form, setForm] = useState({ email: '', password: '' });
  const [errors, setErrors] = useState({});
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setForm((current) => ({ ...current, [name]: value }));
    setErrors((current) => {
      if (!current[name]) return current;

      const next = { ...current };
      delete next[name];
      return next;
    });
    setError('');
  };

  const handleBlur = (e) => {
    const { name } = e.target;
    const fieldError = validateLoginForm(form)[name];

    if (fieldError) {
      setErrors((current) => ({ ...current, [name]: fieldError }));
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const normalizedForm = {
      email: form.email.trim().toLowerCase(),
      password: form.password,
    };
    const validationErrors = validateLoginForm(normalizedForm);

    if (Object.keys(validationErrors).length > 0) {
      setErrors(validationErrors);
      setError('Please correct the highlighted fields before signing in.');
      return;
    }

    setForm(normalizedForm);
    setErrors({});
    setError('');
    setLoading(true);

    try {
      const authenticatedUser = await login(normalizedForm);
      navigate(location.state?.from || '/dashboard', {
        replace: true,
        state: {
          flash: {
            variant: 'success',
            message: `Welcome back, ${authenticatedUser.name}.`,
          },
        },
      });
    } catch (err) {
      if (Object.keys(err.fields || {}).length > 0) {
        setErrors(err.fields);
      }
      setError(err.message || 'Login failed. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  if (isAuthenticated) {
    return <Navigate to="/dashboard" replace />;
  }

  return (
    <Layout>
      <AuthPageLayout
        eyebrow="WELCOME BACK"
        title="Sign in to your account"
        subtitle="Use your campus credentials to continue to your personalized workspace."
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
            <div className="mb-4">
              <label className="form-label" htmlFor="login-email">Email address</label>
              <input
                id="login-email"
                type="email"
                name="email"
                autoComplete="email"
                className={`form-control form-control-lg${errors.email ? ' is-invalid' : ''}`}
                value={form.email}
                onChange={handleChange}
                onBlur={handleBlur}
                placeholder="student@nubtk.edu"
                aria-invalid={Boolean(errors.email)}
                aria-describedby={errors.email ? 'login-email-error' : undefined}
                required
              />
              {errors.email && <div id="login-email-error" className="invalid-feedback">{errors.email}</div>}
            </div>
            <div className="mb-4">
              <div className="d-flex align-items-center justify-content-between gap-3">
                <label className="form-label" htmlFor="login-password">Password</label>
                <span className="small text-secondary">Keep your credentials private</span>
              </div>
              <input
                id="login-password"
                type="password"
                name="password"
                autoComplete="current-password"
                className={`form-control form-control-lg${errors.password ? ' is-invalid' : ''}`}
                value={form.password}
                onChange={handleChange}
                onBlur={handleBlur}
                placeholder="••••••••"
                aria-invalid={Boolean(errors.password)}
                aria-describedby={errors.password ? 'login-password-error' : undefined}
                required
              />
              {errors.password && <div id="login-password-error" className="invalid-feedback">{errors.password}</div>}
            </div>
            <button type="submit" className="btn btn-primary rounded-3 w-100 py-2 mb-4" disabled={loading} aria-busy={loading}>
              {loading && <span className="spinner-border spinner-border-sm me-2" aria-hidden="true" />}
              {loading ? 'Signing in...' : 'Sign in securely'}
            </button>
          </form>

          <div className="auth-form-divider"><span>New to NUBTK Campus?</span></div>
          <Link to="/register" className="btn btn-outline-primary rounded-3 w-100">
            Create an account
          </Link>
        </div>
      </AuthPageLayout>
    </Layout>
  );
}

export default LoginPage;
