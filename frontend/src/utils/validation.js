const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
const ALLOWED_ROLES = ['student', 'faculty', 'admin'];

const validateEmail = (email) => {
  const value = email.trim();

  if (!value) return 'Please enter your email address.';
  if (value.length > 255) return 'Email addresses cannot exceed 255 characters.';
  if (!EMAIL_PATTERN.test(value)) return 'Please enter a valid email address.';

  return '';
};

export const validateLoginForm = ({ email, password }) => {
  const errors = {};
  const emailError = validateEmail(email);

  if (emailError) errors.email = emailError;
  if (!password) {
    errors.password = 'Please enter your password.';
  } else if (password.length > 255) {
    errors.password = 'Passwords cannot exceed 255 characters.';
  }

  return errors;
};

export const validateRegistrationForm = ({
  name,
  email,
  password,
  password_confirmation: passwordConfirmation,
  role,
}) => {
  const errors = {};
  const trimmedName = name.trim();
  const emailError = validateEmail(email);

  if (!trimmedName) {
    errors.name = 'Please enter your full name.';
  } else if (trimmedName.length < 2) {
    errors.name = 'Your full name must be at least 2 characters.';
  } else if (trimmedName.length > 255) {
    errors.name = 'Your full name cannot exceed 255 characters.';
  }

  if (emailError) errors.email = emailError;

  if (!ALLOWED_ROLES.includes(role)) {
    errors.role = 'Please select a valid account role.';
  }

  if (!password) {
    errors.password = 'Please create a password.';
  } else if (password.length > 255) {
    errors.password = 'Passwords cannot exceed 255 characters.';
  } else if (
    password.length < 8
    || !/[a-z]/.test(password)
    || !/[A-Z]/.test(password)
    || !/\d/.test(password)
    || !/[^A-Za-z0-9]/.test(password)
  ) {
    errors.password = 'Use at least 8 characters with uppercase, lowercase, a number, and a symbol.';
  }

  if (!passwordConfirmation) {
    errors.password_confirmation = 'Please confirm your password.';
  } else if (passwordConfirmation !== password) {
    errors.password_confirmation = 'The password confirmation does not match.';
  }

  return errors;
};
