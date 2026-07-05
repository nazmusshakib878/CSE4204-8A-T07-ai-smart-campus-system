import { useState } from 'react';
import Layout from '../components/Layout';

function AiAssistantPage() {
  const [question, setQuestion] = useState('');
  const prompts = [
    'Explain this topic simply',
    'Create a study plan for this week',
    'Recommend improvement areas',
    'Summarize my academic tasks',
  ];

  return (
    <Layout title="AI Assistant" subtitle="Ask questions, get study guidance, and receive smart recommendations.">
      <div className="assistant-shell">
        <div className="assistant-panel">
          <div className="assistant-orb">AI</div>
          <div>
            <span className="eyebrow-label text-primary">Smart guidance</span>
            <h3>How can I help with your study today?</h3>
            <p>Use the assistant for concepts, planning, recommendations, and quick academic direction.</p>
          </div>
        </div>

        <div className="row g-4">
          <div className="col-lg-8">
            <div className="card border-0 shadow-sm rounded-4 p-4 h-100">
              <div className="assistant-response rounded-4 p-4 mb-3">
                <span className="assistant-message-label">Assistant preview</span>
                <p className="mb-0">Ask a question and your answer will appear here. Try asking for a topic explanation, study routine, or improvement advice.</p>
              </div>
              <div className="assistant-composer input-group">
                <label className="visually-hidden" htmlFor="assistant-question">Question for the AI assistant</label>
                <input
                  id="assistant-question"
                  className="form-control form-control-lg"
                  placeholder="Type your question here..."
                  value={question}
                  onChange={(event) => setQuestion(event.target.value)}
                />
                <button type="button" className="btn btn-primary px-4">Send</button>
              </div>
            </div>
          </div>

          <div className="col-lg-4">
            <div className="card border-0 shadow-sm rounded-4 p-4 h-100">
              <h5 className="fw-bold text-dark mb-3">Suggested prompts</h5>
              <div className="d-grid gap-2">
                {prompts.map((prompt) => (
                  <button
                    key={prompt}
                    type="button"
                    className="btn btn-outline-secondary text-start"
                    onClick={() => setQuestion(prompt)}
                  >
                    {prompt}
                  </button>
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>
    </Layout>
  );
}

export default AiAssistantPage;