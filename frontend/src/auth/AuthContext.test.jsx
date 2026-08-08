import { render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { AuthProvider } from './AuthContext';
import { useAuth } from './auth-context';
import { getProfile } from '../services/api';

vi.mock('../services/api', () => ({
  getProfile: vi.fn(),
  loginUser: vi.fn(),
  logoutUser: vi.fn(),
  registerUser: vi.fn(),
}));

function SessionState() {
  const { initializing, isAuthenticated, user } = useAuth();
  return (
    <div>
      <span>{initializing ? 'initializing' : 'ready'}</span>
      <span>{isAuthenticated ? 'authenticated' : 'anonymous'}</span>
      <span>{user?.name || 'no-user'}</span>
    </div>
  );
}

describe('AuthProvider session restoration', () => {
  beforeEach(() => {
    localStorage.clear();
    vi.clearAllMocks();
    localStorage.setItem('auth_token', 'stored-token');
    localStorage.setItem('user', JSON.stringify({ id: 1, name: 'Stored User', role: 'student' }));
  });

  it('keeps the stored session after a temporary network failure', async () => {
    getProfile.mockRejectedValueOnce(new Error('Network error'));

    render(<AuthProvider><SessionState /></AuthProvider>);

    await waitFor(() => expect(screen.getByText('ready')).toBeInTheDocument());
    expect(screen.getByText('authenticated')).toBeInTheDocument();
    expect(screen.getByText('Stored User')).toBeInTheDocument();
    expect(localStorage.getItem('auth_token')).toBe('stored-token');
  });

  it('clears the stored session when the API rejects authorization', async () => {
    getProfile.mockRejectedValueOnce(Object.assign(new Error('Unauthorized'), { status: 401 }));

    render(<AuthProvider><SessionState /></AuthProvider>);

    await waitFor(() => expect(screen.getByText('anonymous')).toBeInTheDocument());
    expect(localStorage.getItem('auth_token')).toBeNull();
    expect(localStorage.getItem('user')).toBeNull();
  });
});
