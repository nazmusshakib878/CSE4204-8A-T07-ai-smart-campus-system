export const getDashboardPath = (user) => {
  if (user?.role === 'admin') return '/admin';
  if (user?.role === 'faculty') return '/faculty-dashboard';
  return '/dashboard';
};
