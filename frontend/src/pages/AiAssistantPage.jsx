import { useState } from 'react';
import Layout from '../components/Layout';
import { StatusAlert } from '../components/Feedback';
import { askAiAssistant } from '../services/api';

function AiAssistantPage() {
  const [question, setQuestion] = useState('');
  const [answer, setAnswer] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const prompts = [
    'Explain my academic progress',
    'Create a study plan for this week',
    'Which areas should I improve?',
    'Summarize my academic tasks',
    'How can I improve my attendance?',
    'Explain my weak courses',
  ];

  const submit = async (event) => {
    event?.preventDefault();
    const trimmedQuestion = question.trim();
    if (!trimmedQuestion || loading) return;
    setLoading(true);
    setError('');
    try {
      const response = await askAiAssistant(trimmedQuestion);
      setAnswer(response.data?.data?.answer || '');
    } catch (requestError) {
      setError(requestError.message || 'AI Assistant is temporarily unavailable. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <Layout title="AI Assistant" subtitle="Ask questions, get study guidance, and receive smart recommendations.">
      <div className="assistant-shell">
        <div className="assistant-panel">
          <div className="assistant-orb">AI</div>
          <div>
            <span className="eyebrow-label text-primary">Smart guidance</span>
            <h3>How can I help with your study today?</h3>
            <p>Ask about your available academic information, study planning, tasks, and campus notices.</p>
          </div>
        </div>

        <div className="row g-4">
          <div className="col-lg-8">
            <div className="card border-0 shadow-sm rounded-4 p-4 h-100">
              {error && <StatusAlert variant="danger" message={error} onDismiss={() => setError('')} />}
              <div className="assistant-response rounded-4 p-4 mb-3" aria-live="polite">
                <span className="assistant-message-label">{loading ? 'AI is thinking...' : answer ? 'AI response' : 'AI Assistant'}</span>
                <p className="mb-0">{loading ? 'AI is thinking...' : answer || 'Ask a question to receive guidance based on your available campus data.'}</p>
              </div>
              <form className="assistant-composer input-group" onSubmit={submit}>
                <label className="visually-hidden" htmlFor="assistant-question">Question for the AI assistant</label>
                <input
                  id="assistant-question"
                  className="form-control form-control-lg"
                  placeholder="Type your question here..."
                  value={question}
                  onChange={(event) => setQuestion(event.target.value)}
                />
                <button type="submit" className="btn btn-primary px-4" disabled={loading || !question.trim()}>
                  {loading ? 'Sending...' : 'Send'}
                </button>
              </form>
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
                    disabled={loading}
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
