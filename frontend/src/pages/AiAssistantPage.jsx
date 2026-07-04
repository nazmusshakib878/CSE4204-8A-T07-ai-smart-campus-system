import Layout from '../components/Layout';

function AiAssistantPage() {
  return (
    <Layout title="AI Interaction Page" subtitle="Ask questions, get study guidance, and receive smart recommendations.">
      <div className="row g-4">
        <div className="col-lg-8">
          <div className="card border-0 shadow-sm rounded-4 p-4 h-100">
            <h5 className="fw-bold text-dark mb-3">Ask the AI assistant</h5>
            <div className="rounded-4 bg-light p-4 mb-3" style={{ minHeight: 220 }}>
              <p className="text-secondary mb-0">Here you can interact with the AI assistant for study help, recommendations, or academic guidance.</p>
            </div>
            <div className="input-group">
              <input className="form-control form-control-lg" placeholder="Type your question here..." />
              <button className="btn btn-primary">Send</button>
            </div>
          </div>
        </div>

        <div className="col-lg-4">
          <div className="card border-0 shadow-sm rounded-4 p-4 h-100">
            <h5 className="fw-bold text-dark mb-3">Suggested prompts</h5>
            <div className="d-grid gap-2">
              <button className="btn btn-outline-secondary text-start">Explain this topic simply</button>
              <button className="btn btn-outline-secondary text-start">Suggest study plan</button>
              <button className="btn btn-outline-secondary text-start">Recommend improvement areas</button>
            </div>
          </div>
        </div>
      </div>
    </Layout>
  );
}

export default AiAssistantPage;
