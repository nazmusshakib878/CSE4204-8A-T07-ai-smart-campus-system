import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import Layout from '../components/Layout';
import { loginUser } from '../services/api';

function LoginPage() {
  const navigate = useNavigate();
  const [form, setForm] = useState({ email: '', password: '' });
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
      const response = await loginUser(form);
      localStorage.setItem('auth_token', response.data.token);
      localStorage.setItem('user', JSON.stringify(response.data.user));
      navigate('/dashboard');
    } catch (err) {
      setError(err?.response?.data?.message || 'Login failed. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <Layout title="Sign In" subtitle="Access your student or admin dashboard securely.">
      <div className="row justify-content-center">
        <div className="col-lg-5">
          <div className="card border-0 shadow-lg rounded-4 p-4">
            <h4 className="fw-bold text-dark mb-3">Welcome back</h4>
            <p className="text-secondary mb-4">Enter your credentials to continue.</p>

            {error && <div className="alert alert-danger">{error}</div>}

            <form onSubmit={handleSubmit}>
              <div className="mb-3">
                <label className="form-label">Email address</label>
                <input type="email" name="email" className="form-control form-control-lg" value={form.email} onChange={handleChange} placeholder="student@nubtk.edu" required />
              </div>
              <div className="mb-3">
                <label className="form-label">Password</label>
                <input type="password" name="password" className="form-control form-control-lg" value={form.password} onChange={handleChange} placeholder="••••••••" required />
              </div>
              <button type="submit" className="btn btn-primary rounded-pill w-100 py-2 mb-3" disabled={loading}>
                {loading ? 'Signing in...' : 'Sign In'}
              </button>
            </form>

            <p className="text-center text-secondary mb-0">
              New here? <Link to="/register" className="text-primary">Create an account</Link>
            </p>
          </div>
        </div>
      </div>
    </Layout>
  );
}

export default LoginPage;
