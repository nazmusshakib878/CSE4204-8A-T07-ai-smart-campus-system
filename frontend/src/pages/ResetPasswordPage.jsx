import { useEffect, useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import Layout from '../components/Layout';
import AuthPageLayout from '../components/AuthPageLayout';
import { StatusAlert } from '../components/Feedback';
import { resetPassword } from '../services/api';
import { validateResetPasswordForm } from '../utils/validation';

function ResetPasswordPage() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token') || '';
  const emailFromUrl = searchParams.get('email') || '';
  const [form, setForm] = useState({
    email: emailFromUrl,
    token,
    password: '',
    password_confirmation: '',
  });
  const [errors, setErrors] = useState({});
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    setForm((current) => ({
      ...current,
      email: emailFromUrl,
      token,
    }));
  }, [emailFromUrl, token]);

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
    const fieldError = validateResetPasswordForm(form)[name];

    if (fieldError) {
      setErrors((current) => ({ ...current, [name]: fieldError }));
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const normalizedForm = {
      email: form.email.trim().toLowerCase(),
      token: form.token,
      password: form.password,
      password_confirmation: form.password_confirmation,
    };
    const validationErrors = validateResetPasswordForm(normalizedForm);

    if (Object.keys(validationErrors).length > 0) {
      setErrors(validationErrors);
      setError('Please correct the highlighted fields before continuing.');
      return;
    }

    setForm(normalizedForm);
    setErrors({});
    setError('');
    setLoading(true);

    try {
      const response = await resetPassword(normalizedForm);
      navigate('/login', {
        replace: true,
        state: {
          flash: {
            variant: 'success',
            message: response.data.message,
          },
        },
      });
    } catch (err) {
      if (Object.keys(err.fields || {}).length > 0) {
        setErrors(err.fields);
      }
      setError(err.message || 'Unable to reset your password.');
    } finally {
      setLoading(false);
    }
  };

  const missingToken = !token;

  return (
    <Layout>
      <AuthPageLayout
        eyebrow="PASSWORD RESET"
        title="Create a new password"
        subtitle="Use the secure link from your email to set a new password and return to sign in."
      >
        <div className="auth-form-card">
          {missingToken && (
            <StatusAlert
              variant="warning"
              message="This reset link is missing its token. Request a new password reset email."
            />
          )}

          {error && (
            <StatusAlert
              variant="danger"
              message={error}
              onDismiss={() => setError('')}
            />
          )}

          <form onSubmit={handleSubmit} noValidate>
            <div className="mb-4">
              <label className="form-label" htmlFor="reset-email">Email address</label>
              <input
                id="reset-email"
                type="email"
                name="email"
                autoComplete="email"
                className={`form-control form-control-lg${errors.email ? ' is-invalid' : ''}`}
                value={form.email}
                onChange={handleChange}
                onBlur={handleBlur}
                placeholder="student@nubtk.edu"
                aria-invalid={Boolean(errors.email)}
                aria-describedby={errors.email ? 'reset-email-error' : undefined}
                required
              />
              {errors.email && <div id="reset-email-error" className="invalid-feedback">{errors.email}</div>}
            </div>

            <input type="hidden" name="token" value={form.token} />

            <div className="mb-4">
              <label className="form-label" htmlFor="reset-password">New password</label>
              <input
                id="reset-password"
                type="password"
                name="password"
                autoComplete="new-password"
                className={`form-control form-control-lg${errors.password ? ' is-invalid' : ''}`}
                value={form.password}
                onChange={handleChange}
                onBlur={handleBlur}
                placeholder="Create a strong password"
                aria-invalid={Boolean(errors.password)}
                aria-describedby={errors.password ? 'reset-password-error' : undefined}
                required
              />
              {errors.password && <div id="reset-password-error" className="invalid-feedback">{errors.password}</div>}
            </div>

            <div className="mb-4">
              <label className="form-label" htmlFor="reset-password-confirmation">Confirm new password</label>
              <input
                id="reset-password-confirmation"
                type="password"
                name="password_confirmation"
                autoComplete="new-password"
                className={`form-control form-control-lg${errors.password_confirmation ? ' is-invalid' : ''}`}
                value={form.password_confirmation}
                onChange={handleChange}
                onBlur={handleBlur}
                placeholder="Re-enter your new password"
                aria-invalid={Boolean(errors.password_confirmation)}
                aria-describedby={errors.password_confirmation ? 'reset-password-confirmation-error' : undefined}
                required
              />
              {errors.password_confirmation && <div id="reset-password-confirmation-error" className="invalid-feedback">{errors.password_confirmation}</div>}
            </div>

            <button type="submit" className="btn btn-primary rounded-3 w-100 py-2 mb-4" disabled={loading || missingToken} aria-busy={loading}>
              {loading && <span className="spinner-border spinner-border-sm me-2" aria-hidden="true" />}
              {loading ? 'Resetting password...' : 'Reset password'}
            </button>
          </form>

          <div className="auth-form-divider"><span>Need a new link?</span></div>
          <Link to="/forgot-password" className="btn btn-outline-primary rounded-3 w-100">
            Request another reset email
          </Link>
        </div>
      </AuthPageLayout>
    </Layout>
  );
}

export default ResetPasswordPage;