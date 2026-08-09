import { act, fireEvent, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import AiAssistantPage from './AiAssistantPage';
import { askAiAssistant, getAiConversation, startAiConversation } from '../services/api';

const authState = vi.hoisted(() => ({ role: 'student' }));

vi.mock('../auth/auth-context', () => ({
  useAuth: () => ({ user: { role: authState.role } }),
}));

vi.mock('../components/Layout', () => ({ default: ({ children }) => <div>{children}</div> }));
vi.mock('../components/Feedback', () => ({ StatusAlert: ({ message }) => <div>{message}</div> }));
vi.mock('../services/api', () => ({
  askAiAssistant: vi.fn(),
  getAiConversation: vi.fn(),
  startAiConversation: vi.fn(),
}));

describe('AiAssistantPage request locking', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    authState.role = 'student';
    window.sessionStorage.clear();
    getAiConversation.mockResolvedValue({ data: { data: { conversation_id: null, messages: [] } } });
    Element.prototype.scrollIntoView = vi.fn();
  });

  it('shows Faculty suggestions without changing Student suggestions', async () => {
    authState.role = 'faculty';
    render(<AiAssistantPage />);

    expect(await screen.findByRole('button', { name: 'Which courses am I teaching?' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Show my assigned classes.' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: "Give me a summary of my students' performance." })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Which students have low attendance?' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Help me prepare a lesson plan.' })).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'What courses am I taking?' })).not.toBeInTheDocument();
  });

  it('starts a backend conversation and sends the next message without old context', async () => {
    getAiConversation.mockResolvedValue({
      data: {
        data: {
          conversation_id: 42,
          messages: [{ id: 1, role: 'assistant', content: 'Previous conversation' }],
        },
      },
    });
    startAiConversation.mockResolvedValue({
      data: { data: { conversation_id: 99, messages: [] } },
    });
    askAiAssistant.mockResolvedValue({
      data: { data: { answer: 'Fresh answer', conversation_id: 99 } },
    });
    const user = userEvent.setup();
    render(<AiAssistantPage />);

    expect(await screen.findByText('Previous conversation')).toBeInTheDocument();
    await user.click(screen.getByRole('button', { name: 'New conversation' }));

    expect(screen.queryByText('Previous conversation')).not.toBeInTheDocument();
    expect(screen.getByText(/Say hello or ask anything/)).toBeInTheDocument();
    expect(startAiConversation).toHaveBeenCalledTimes(1);
    expect(window.sessionStorage.getItem('ai-assistant-new-conversation')).toBeNull();

    await user.type(screen.getByPlaceholderText('Message the assistant...'), 'Fresh topic');
    await user.click(screen.getByRole('button', { name: 'Send' }));

    await waitFor(() => expect(askAiAssistant).toHaveBeenCalledWith('Fresh topic', 99));
    expect(await screen.findByText('Fresh answer')).toBeInTheDocument();
  });

  it('sends only one POST when submit fires twice while Gemini is processing', async () => {
    let resolveRequest;
    askAiAssistant.mockReturnValue(new Promise((resolve) => {
      resolveRequest = resolve;
    }));
    const user = userEvent.setup();
    render(<AiAssistantPage />);

    const input = await screen.findByPlaceholderText('Message the assistant...');
    await user.type(input, 'Hello');
    const send = screen.getByRole('button', { name: 'Send' });

    fireEvent.click(send);
    fireEvent.click(send);

    await waitFor(() => expect(askAiAssistant).toHaveBeenCalledTimes(1));
    expect(screen.getByText('Thinking...')).toBeInTheDocument();
    expect(send).toBeDisabled();

    await act(async () => {
      resolveRequest({ data: { data: { answer: 'Hi!', conversation_id: 1 } } });
    });
  });
});
