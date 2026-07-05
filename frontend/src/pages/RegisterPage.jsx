import { useState } from 'react';
import { Link, Navigate, useNavigate } from 'react-router-dom';
import Layout from '../components/Layout';
import { useAuth } from '../auth/auth-context';

function RegisterPage() {
  const navigate = useNavigate();
  const { isAuthenticated, register } = useAuth();
  const [form, setForm] = useState({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'student'
  });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      await register(form);
      navigate('/dashboard', { replace: true });
    } catch (err) {
      setError(err.message || 'Registration failed.');
    } finally {
      setLoading(false);
    }
  };

  if (isAuthenticated) {
    return <Navigate to="/dashboard" replace />;
  }

  return (
    <Layout title="Create Account" subtitle="Join the smart campus platform and access your academic tools.">
      <div className="row justify-content-center">
        <div className="col-lg-7">
          <div className="card border-0 shadow-lg rounded-4 p-4">
            <h4 className="fw-bold text-dark mb-3">Register as a new user</h4>
            <p className="text-secondary mb-4">Choose your role and set up your profile.</p>

            {error && <div className="alert alert-danger">{error}</div>}

            <form onSubmit={handleSubmit}>
              <div className="row g-3">
                <div className="col-md-6">
                  <label className="form-label">Full name</label>
                  <input name="name" className="form-control" value={form.name} onChange={handleChange} placeholder="Ayesha Rahman" required />
                </div>
                <div className="col-md-6">
                  <label className="form-label">Email</label>
                  <input type="email" name="email" className="form-control" value={form.email} onChange={handleChange} placeholder="you@example.com" required />
                </div>
                <div className="col-md-6">
                  <label className="form-label">Role</label>
                  <select name="role" className="form-select" value={form.role} onChange={handleChange}>
                    <option value="student">Student</option>
                    <option value="faculty">Faculty</option>
                    <option value="admin">Admin</option>
                  </select>
                </div>
                <div className="col-md-6">
                  <label className="form-label">Password</label>
                  <input type="password" name="password" className="form-control" value={form.password} onChange={handleChange} placeholder="••••••••" required />
                </div>
                <div className="col-md-6">
                  <label className="form-label">Confirm password</label>
                  <input type="password" name="password_confirmation" className="form-control" value={form.password_confirmation} onChange={handleChange} placeholder="••••••••" required />
                </div>
              </div>

              <button type="submit" className="btn btn-primary rounded-pill w-100 py-2 mt-4" disabled={loading}>
                {loading ? 'Creating account...' : 'Create Account'}
              </button>
            </form>

            <p className="text-center text-secondary mt-3 mb-0">
              Already have an account? <Link to="/login" className="text-primary">Sign in</Link>
            </p>
          </div>
        </div>
      </div>
    </Layout>
  );
}

export default RegisterPage;
