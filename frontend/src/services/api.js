import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  headers: {
    Accept: 'application/json',
  },
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && localStorage.getItem('auth_token')) {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      window.dispatchEvent(new Event('auth:unauthorized'));
    }

    return Promise.reject(error);
  }
);

const normalizeError = (error) => {
  if (error.response) {
    const data = error.response.data;
    if (data?.errors) {
      return Object.values(data.errors).flat().join(' ');
    }
    if (data?.message && typeof data.message === 'string') {
      return data.message;
    }
    return error.response.statusText || 'Request failed.';
  }

  return error.message || 'Network error. Please try again.';
};

export const registerUser = async (payload) => {
  try {
    return await api.post('/register', payload);
  } catch (error) {
    const normalizedError = new Error(normalizeError(error));
    normalizedError.status = error.response?.status;
    throw normalizedError;
  }
};

export const loginUser = async (payload) => {
  try {
    return await api.post('/login', payload);
  } catch (error) {
    const normalizedError = new Error(normalizeError(error));
    normalizedError.status = error.response?.status;
    throw normalizedError;
  }
};

export const logoutUser = async () => {
  try {
    return await api.post('/logout');
  } catch (error) {
    const normalizedError = new Error(normalizeError(error));
    normalizedError.status = error.response?.status;
    throw normalizedError;
  }
};

export const getProfile = async () => {
  try {
    return await api.get('/profile');
  } catch (error) {
    const normalizedError = new Error(normalizeError(error));
    normalizedError.status = error.response?.status;
    throw normalizedError;
  }
};

export const getLearningResources = async () => {
  try {
    return await api.get('/learning-resources');
  } catch (error) {
    throw new Error(normalizeError(error));
  }
};

export const getTasks = async () => {
  try {
    return await api.get('/tasks');
  } catch (error) {
    throw new Error(normalizeError(error));
  }
};

export const getRecommendations = async () => {
  try {
    return await api.get('/recommendations');
  } catch (error) {
    throw new Error(normalizeError(error));
  }
};

export const createTask = async (payload) => {
  try {
    return await api.post('/tasks', payload);
  } catch (error) {
    throw new Error(normalizeError(error));
  }
};

export const deleteTask = async (id) => {
  try {
    return await api.delete(`/tasks/${id}`);
  } catch (error) {
    throw new Error(normalizeError(error));
  }
};

export default api;
