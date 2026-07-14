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
    if (data?.message && typeof data.message === 'string') {
      return data.message;
    }
    if (data?.errors) {
      return Object.values(data.errors).flat().join(' ');
    }
    return error.response.statusText || 'Request failed.';
  }

  return error.message || 'Network error. Please try again.';
};

const createApiError = (error) => {
  const normalizedError = new Error(normalizeError(error));
  normalizedError.status = error.response?.status;
  normalizedError.fields = Object.fromEntries(
    Object.entries(error.response?.data?.errors || {}).map(([field, messages]) => [
      field,
      Array.isArray(messages) ? messages[0] : messages,
    ])
  );

  return normalizedError;
};

export const registerUser = async (payload) => {
  try {
    return await api.post('/register', payload);
  } catch (error) {
    throw createApiError(error);
  }
};

export const loginUser = async (payload) => {
  try {
    return await api.post('/login', payload);
  } catch (error) {
    throw createApiError(error);
  }
};

export const logoutUser = async () => {
  try {
    return await api.post('/logout');
  } catch (error) {
    throw createApiError(error);
  }
};

export const createAdmin = async (payload) => {
  try {
    return await api.post('/admin/create-admin', payload);
  } catch (error) {
    throw createApiError(error);
  }
};

export const getPendingUsers = async () => {
  try {
    return await api.get('/admin/pending-users');
  } catch (error) {
    throw createApiError(error);
  }
};

export const updateUserApproval = async (id, approvalStatus) => {
  try {
    return await api.patch(`/admin/users/${id}/approval`, { approval_status: approvalStatus });
  } catch (error) {
    throw createApiError(error);
  }
};


export const getDepartments = async () => {
  try {
    return await api.get('/departments');
  } catch (error) {
    throw createApiError(error);
  }
};

export const getAdminDepartments = async () => {
  try {
    return await api.get('/admin/departments');
  } catch (error) {
    throw createApiError(error);
  }
};

export const createDepartment = async (payload) => {
  try {
    return await api.post('/admin/departments', payload);
  } catch (error) {
    throw createApiError(error);
  }
};

export const deleteDepartment = async (id) => {
  try {
    return await api.delete(`/admin/departments/${id}`);
  } catch (error) {
    throw createApiError(error);
  }
};
export const getProfile = async () => {
  try {
    return await api.get('/profile');
  } catch (error) {
    throw createApiError(error);
  }
};

export const getLearningResources = async () => {
  try {
    return await api.get('/learning-resources');
  } catch (error) {
    throw createApiError(error);
  }
};

export const createLearningResource = async (payload) => {
  try {
    return await api.post('/learning-resources', payload);
  } catch (error) {
    throw createApiError(error);
  }
};

export const getTasks = async () => {
  try {
    return await api.get('/tasks');
  } catch (error) {
    throw createApiError(error);
  }
};

export const getRecommendations = async () => {
  try {
    return await api.get('/recommendations');
  } catch (error) {
    throw createApiError(error);
  }
};

export const createTask = async (payload) => {
  try {
    return await api.post('/tasks', payload);
  } catch (error) {
    throw createApiError(error);
  }
};

export const deleteTask = async (id) => {
  try {
    return await api.delete(`/tasks/${id}`);
  } catch (error) {
    throw createApiError(error);
  }
};

export const getNotices = async () => {
  try {
    return await api.get('/notices');
  } catch (error) {
    throw createApiError(error);
  }
};

export const createNotice = async (payload) => {
  try {
    return await api.post('/notices', payload);
  } catch (error) {
    throw createApiError(error);
  }
};

export const deleteNotice = async (id) => {
  try {
    return await api.delete(`/notices/${id}`);
  } catch (error) {
    throw createApiError(error);
  }
};

export const getStudentMonitoring = async () => {
  try {
    return await api.get('/faculty/student-monitoring');
  } catch (error) {
    throw createApiError(error);
  }
};

export const analyzeStudentRisk = async (studentDatabaseId) => {
  try {
    return await api.post(`/faculty/students/${studentDatabaseId}/analyze-risk`);
  } catch (error) {
    throw createApiError(error);
  }
};

export const getAcademicManagement = async () => {
  try { return await api.get('/academic-management'); }
  catch (error) { throw createApiError(error); }
};

export const createCourse = async (payload) => {
  try { return await api.post('/academic-management/courses', payload); }
  catch (error) { throw createApiError(error); }
};

export const updateCourse = async (id, payload) => {
  try { return await api.put(`/academic-management/courses/${id}`, payload); }
  catch (error) { throw createApiError(error); }
};

export const deleteCourse = async (id) => {
  try { return await api.delete(`/academic-management/courses/${id}`); }
  catch (error) { throw createApiError(error); }
};

export const getCourseWorkspace = async (courseId) => {
  try { return await api.get(`/academic-management/courses/${courseId}/workspace`); }
  catch (error) { throw createApiError(error); }
};

export const enrollCourseStudents = async (courseId, payload) => {
  try { return await api.post(`/academic-management/courses/${courseId}/enrollments`, payload); }
  catch (error) { throw createApiError(error); }
};

export const removeCourseEnrollment = async (courseId, enrollmentId) => {
  try { return await api.delete(`/academic-management/courses/${courseId}/enrollments/${enrollmentId}`); }
  catch (error) { throw createApiError(error); }
};

export const saveCourseAttendance = async (courseId, payload) => {
  try { return await api.put(`/academic-management/courses/${courseId}/attendance`, payload); }
  catch (error) { throw createApiError(error); }
};

export const saveCourseGrades = async (courseId, payload) => {
  try { return await api.put(`/academic-management/courses/${courseId}/grades`, payload); }
  catch (error) { throw createApiError(error); }
};

export const saveStudentPerformance = async (courseId, studentId, payload) => {
  try { return await api.put(`/academic-management/courses/${courseId}/students/${studentId}/performance`, payload); }
  catch (error) { throw createApiError(error); }
};

export default api;
