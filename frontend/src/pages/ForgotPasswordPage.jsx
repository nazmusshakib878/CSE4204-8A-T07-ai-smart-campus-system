import { useState } from 'react';
import { Link } from 'react-router-dom';
import Layout from '../components/Layout';
import AuthPageLayout from '../components/AuthPageLayout';
import { StatusAlert } from '../components/Feedback';
import { forgotPassword } from '../services/api';
import { validateForgotPasswordForm } from '../utils/validation';

function ForgotPasswordPage() {
  const [form, setForm] = useState({ email: '' });
  const [errors, setErrors] = useState({});
  const [message, setMessage] = useState('');
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
    setMessage('');
  };

  const handleBlur = (e) => {
    const { name } = e.target;
    const fieldError = validateForgotPasswordForm(form)[name];

    if (fieldError) {
      setErrors((current) => ({ ...current, [name]: fieldError }));
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const normalizedForm = {
      email: form.email.trim().toLowerCase(),
    };
    const validationErrors = validateForgotPasswordForm(normalizedForm);

    if (Object.keys(validationErrors).length > 0) {
      setErrors(validationErrors);
      setError('Please correct the highlighted field before continuing.');
      return;
    }

    setForm(normalizedForm);
    setErrors({});
    setError('');
    setMessage('');
    setLoading(true);

    try {
      const response = await forgotPassword(normalizedForm);
      setMessage(response.data.message);
    } catch (err) {
      if (Object.keys(err.fields || {}).length > 0) {
        setErrors(err.fields);
      }
      setError(err.message || 'Unable to send the reset link.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <Layout>
      <AuthPageLayout
        eyebrow="PASSWORD RECOVERY"
        title="Reset your password"
        subtitle="Enter the email associated with your account and we will send a reset link."
      >
        <div className="auth-form-card">
          {message && (
            <StatusAlert
              variant="success"
              message={message}
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
              <label className="form-label" htmlFor="forgot-email">Email address</label>
              <input
                id="forgot-email"
                type="email"
                name="email"
                autoComplete="email"
                className={`form-control form-control-lg${errors.email ? ' is-invalid' : ''}`}
                value={form.email}
                onChange={handleChange}
                onBlur={handleBlur}
                placeholder="student@nubtk.edu"
                aria-invalid={Boolean(errors.email)}
                aria-describedby={errors.email ? 'forgot-email-error' : undefined}
                required
              />
              {errors.email && <div id="forgot-email-error" className="invalid-feedback">{errors.email}</div>}
            </div>

            <button type="submit" className="btn btn-primary rounded-3 w-100 py-2 mb-4" disabled={loading} aria-busy={loading}>
              {loading && <span className="spinner-border spinner-border-sm me-2" aria-hidden="true" />}
              {loading ? 'Sending reset link...' : 'Send reset link'}
            </button>
          </form>

          <div className="auth-form-divider"><span>Remember your password?</span></div>
          <Link to="/login" className="btn btn-outline-primary rounded-3 w-100">
            Back to sign in
          </Link>
        </div>
      </AuthPageLayout>
    </Layout>
  );
}

export default ForgotPasswordPage;