import { useCallback, useEffect, useMemo, useState } from 'react';
import { getProfile, loginUser, logoutUser, registerUser } from '../services/api';
import { AuthContext } from './auth-context';

const TOKEN_KEY = 'auth_token';
const USER_KEY = 'user';

const readStoredUser = () => {
  try {
    return JSON.parse(localStorage.getItem(USER_KEY));
  } catch {
    localStorage.removeItem(USER_KEY);
    return null;
  }
};

const storeSession = (token, user) => {
  localStorage.setItem(TOKEN_KEY, token);
  localStorage.setItem(USER_KEY, JSON.stringify(user));
};

const removeStoredSession = () => {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(USER_KEY);
};

export function AuthProvider({ children }) {
  const [token, setToken] = useState(() => localStorage.getItem(TOKEN_KEY));
  const [user, setUser] = useState(() => (
    localStorage.getItem(TOKEN_KEY) ? readStoredUser() : null
  ));
  const [initializing, setInitializing] = useState(true);

  const clearSession = useCallback(() => {
    removeStoredSession();
    setToken(null);
    setUser(null);
    setInitializing(false);
  }, []);

  useEffect(() => {
    window.addEventListener('auth:unauthorized', clearSession);
    return () => window.removeEventListener('auth:unauthorized', clearSession);
  }, [clearSession]);

  useEffect(() => {
    if (!token) {
      setInitializing(false);
      return;
    }

    let active = true;

    getProfile()
      .then((response) => {
        if (!active) return;

        const currentUser = response.data.user;
        localStorage.setItem(USER_KEY, JSON.stringify(currentUser));
        setUser(currentUser);
      })
      .catch(() => {
        if (active) clearSession();
      })
      .finally(() => {
        if (active) setInitializing(false);
      });

    return () => {
      active = false;
    };
  }, [clearSession, token]);

  const applyAuthResponse = useCallback((response) => {
    const { token: newToken, user: authenticatedUser } = response.data;
    storeSession(newToken, authenticatedUser);
    setToken(newToken);
    setUser(authenticatedUser);
    setInitializing(false);
    return authenticatedUser;
  }, []);

  const login = useCallback(async (credentials) => {
    const response = await loginUser(credentials);
    return applyAuthResponse(response);
  }, [applyAuthResponse]);

  const register = useCallback(async (details) => {
    const response = await registerUser(details);
    return applyAuthResponse(response);
  }, [applyAuthResponse]);

  const logout = useCallback(async () => {
    try {
      if (token) {
        await logoutUser();
      }
    } catch {
      // Local logout must still succeed if the token expired or the API is offline.
    } finally {
      clearSession();
    }
  }, [clearSession, token]);

  const value = useMemo(() => ({
    user,
    initializing,
    isAuthenticated: Boolean(token && user),
    login,
    register,
    logout,
  }), [initializing, login, logout, register, token, user]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
