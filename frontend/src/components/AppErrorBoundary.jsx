import { Component } from 'react';

class AppErrorBoundary extends Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false };
  }

  static getDerivedStateFromError() {
    return { hasError: true };
  }

  render() {
    if (this.state.hasError) {
      return (
        <main className="loading-state loading-state-full-page d-flex flex-column align-items-center justify-content-center gap-3 p-4 text-center">
          <h1 className="h4 mb-0">This page could not be loaded</h1>
          <p className="text-secondary mb-0">Your account data is safe. Please reload the page and try again.</p>
          <div className="d-flex gap-2">
            <button type="button" className="btn btn-primary" onClick={() => window.location.reload()}>Reload page</button>
            <a className="btn btn-outline-secondary" href="/dashboard">Go to dashboard</a>
          </div>
        </main>
      );
    }

    return this.props.children;
  }
}

export default AppErrorBoundary;
