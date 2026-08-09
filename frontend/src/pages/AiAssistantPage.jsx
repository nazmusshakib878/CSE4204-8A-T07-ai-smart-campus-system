import { useEffect, useRef, useState } from 'react';
import ReactMarkdown from 'react-markdown';
import Layout from '../components/Layout';
import { StatusAlert } from '../components/Feedback';
import { useAuth } from '../auth/auth-context';
import { askAiAssistant, getAiConversation, startAiConversation } from '../services/api';

const NEW_CONVERSATION_KEY = 'ai-assistant-new-conversation';

const studentSuggestedPrompts = [
  'Explain recursion with a simple example',
  'What courses am I taking?',
  'How can I improve my programming?',
  'What is my attendance record?',
];

const facultySuggestedPrompts = [
  'Which courses am I teaching?',
  'Show my assigned classes.',
  "Give me a summary of my students' performance.",
  'Which students have low attendance?',
  'Help me prepare a lesson plan.',
];

function AiAssistantPage() {
  const { user } = useAuth();
  const suggestedPrompts = user?.role === 'faculty' ? facultySuggestedPrompts : studentSuggestedPrompts;
  const [question, setQuestion] = useState('');
  const [messages, setMessages] = useState([]);
  const [conversationId, setConversationId] = useState(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [resetting, setResetting] = useState(false);
  const [initializing, setInitializing] = useState(true);
  const endRef = useRef(null);
  const submittingRef = useRef(false);

  useEffect(() => {
    if (window.sessionStorage.getItem(NEW_CONVERSATION_KEY) === 'true') {
      setInitializing(false);
      return undefined;
    }

    let active = true;
    getAiConversation()
      .then((response) => {
        if (!active) return;
        setConversationId(response?.data?.data?.conversation_id ?? null);
        setMessages(response?.data?.data?.messages ?? []);
      })
      .catch((requestError) => {
        if (active) setError(requestError.message || 'Your recent conversation could not be loaded.');
      })
      .finally(() => {
        if (active) setInitializing(false);
      });
    return () => { active = false; };
  }, []);

  useEffect(() => {
    endRef.current?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }, [messages, loading]);

  const startNewConversation = async () => {
    if (initializing || loading || resetting) return;

    window.sessionStorage.setItem(NEW_CONVERSATION_KEY, 'true');
    setConversationId(null);
    setMessages([]);
    setQuestion('');
    setError('');
    setResetting(true);

    try {
      const response = await startAiConversation();
      setConversationId(response?.data?.data?.conversation_id ?? null);
      window.sessionStorage.removeItem(NEW_CONVERSATION_KEY);
    } catch (requestError) {
      setError(requestError.message || 'A fresh conversation could not be started. Please try again.');
    } finally {
      setResetting(false);
    }
  };

  const submit = async (event) => {
    event?.preventDefault();
    const trimmedQuestion = question.trim();
    if (!trimmedQuestion) {
      setError('Please enter a message.');
      return;
    }
    if (submittingRef.current) return;
    submittingRef.current = true;

    const pendingMessage = { id: `pending-${Date.now()}`, role: 'user', content: trimmedQuestion };
    setMessages((current) => [...current, pendingMessage]);
    setQuestion('');
    setLoading(true);
    setError('');

    try {
      const response = await askAiAssistant(trimmedQuestion, conversationId);
      const data = response?.data?.data;
      window.sessionStorage.removeItem(NEW_CONVERSATION_KEY);
      setConversationId(data?.conversation_id ?? conversationId);
      setMessages((current) => [
        ...current,
        { id: `assistant-${Date.now()}`, role: 'assistant', content: data?.answer || '' },
      ]);
    } catch (requestError) {
      setMessages((current) => current.filter((message) => message.id !== pendingMessage.id));
      setQuestion(trimmedQuestion);
      setError(requestError.message || 'AI service is temporarily unavailable. Please try again.');
    } finally {
      submittingRef.current = false;
      setLoading(false);
    }
  };

  return (
    <Layout title="AI Assistant" subtitle="A natural, context-aware Gemini conversation with verified campus data when needed.">
      <div className="assistant-shell">
        <div className="assistant-panel">
          <div className="assistant-orb">AI</div>
          <div>
            <span className="eyebrow-label text-primary">Gemini-powered conversation</span>
            <h3>What would you like to talk about?</h3>
            <p>Ask a general question, continue a topic, or request your available campus information.</p>
          </div>
          <button type="button" className="btn btn-light ms-auto align-self-center" onClick={startNewConversation} disabled={initializing || loading || resetting}>
            New conversation
          </button>
        </div>

        <div className="row g-4">
          <div className="col-lg-8">
            <div className="card border-0 shadow-sm rounded-4 p-4 h-100">
              {error && <StatusAlert variant="danger" message={error} onDismiss={() => setError('')} />}
              <div className="assistant-response assistant-thread rounded-4 p-4 mb-3" aria-live="polite">
                {initializing ? (
                  <p className="mb-0 text-secondary">Loading your recent conversation...</p>
                ) : messages.length === 0 ? (
                  <div className="assistant-empty">
                    <span className="assistant-message-label">AI Assistant</span>
                    <p className="mb-0">Say hello or ask anything. I’ll use your campus records only when your question needs them.</p>
                  </div>
                ) : (
                  messages.map((message) => (
                    <div key={message.id} className={`assistant-bubble assistant-bubble-${message.role}`}>
                      <span>{message.role === 'user' ? 'You' : 'Assistant'}</span>
                      <ReactMarkdown>{message.content}</ReactMarkdown>
                    </div>
                  ))
                )}
                {loading && (
                  <div className="assistant-bubble assistant-bubble-assistant assistant-thinking">
                    <span>Assistant</span>
                    <p>Thinking...</p>
                  </div>
                )}
                <div ref={endRef} />
              </div>
              <form className="assistant-composer input-group" onSubmit={submit}>
                <label className="visually-hidden" htmlFor="assistant-question">Message the AI assistant</label>
                <textarea
                  id="assistant-question"
                  className="form-control form-control-lg"
                  rows="2"
                  maxLength="4000"
                  placeholder="Message the assistant..."
                  value={question}
                  onChange={(event) => setQuestion(event.target.value)}
                  onKeyDown={(event) => {
                    if (event.key === 'Enter' && !event.shiftKey) {
                      event.preventDefault();
                      submit();
                    }
                  }}
                  disabled={initializing || loading || resetting}
                />
                <button type="submit" className="btn btn-primary px-4" disabled={initializing || loading || resetting || !question.trim()}>
                  {loading ? 'Sending...' : 'Send'}
                </button>
              </form>
              <small className="text-secondary mt-2">Enter to send · Shift+Enter for a new line</small>
            </div>
          </div>

          <div className="col-lg-4">
            <div className="card border-0 shadow-sm rounded-4 p-4 h-100">
              <h5 className="fw-bold text-dark mb-3">Try asking</h5>
              <div className="d-grid gap-2">
                {suggestedPrompts.map((prompt) => (
                  <button key={prompt} type="button" className="btn btn-outline-secondary text-start" onClick={() => { setQuestion(prompt); setError(''); }} disabled={loading || resetting}>
                    {prompt}
                  </button>
                ))}
              </div>
              <p className="small text-secondary mt-4 mb-0">Campus-specific answers use only records available for your authenticated account.</p>
            </div>
          </div>
        </div>
      </div>
    </Layout>
  );
}

export default AiAssistantPage;
