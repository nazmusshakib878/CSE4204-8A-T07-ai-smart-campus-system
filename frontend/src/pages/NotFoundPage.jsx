import { Link } from 'react-router-dom';
import Layout from '../components/Layout';

function NotFoundPage() {
  return (
    <Layout>
      <section className="not-found-state card border-0 shadow-sm rounded-4 p-5 text-center mx-auto">
        <p className="text-primary fw-bold text-uppercase small mb-2">Error 404</p>
        <h1 className="fw-bold text-dark mb-3">We couldn’t find that page.</h1>
        <p className="text-secondary mb-4">
          The address may be incorrect, or the page may have moved.
        </p>
        <div className="d-flex flex-column flex-sm-row justify-content-center gap-2">
          <Link to="/" className="btn btn-primary rounded-pill px-4">Return home</Link>
          <Link to="/dashboard" className="btn btn-outline-secondary rounded-pill px-4">Open dashboard</Link>
        </div>
      </section>
    </Layout>
  );
}

export default NotFoundPage;
