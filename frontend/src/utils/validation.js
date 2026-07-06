const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
const PHONE_PATTERN = /^\+?[0-9]{10,15}$/;
const STUDENT_ID_PATTERN = /^[A-Z]{2,5}\d{8,12}$/;
const FACULTY_ID_PATTERN = /^FAC-[A-Z]{2,5}-\d{4}$/;
const ADMIN_ID_PATTERN = /^ADM-\d{3}$/;
const ALLOWED_ROLES = ['student', 'faculty'];

const validateEmail = (email) => {
  const value = email.trim();

  if (!value) return 'Please enter your email address.';
  if (value.length > 255) return 'Email addresses cannot exceed 255 characters.';
  if (!EMAIL_PATTERN.test(value)) return 'Please enter a valid email address.';

  return '';
};

const validatePhone = (phone, requiredMessage = 'Please enter your phone number.') => {
  const value = phone.trim();

  if (!value) return requiredMessage;
  if (value.length > 20) return 'Phone number cannot exceed 20 characters.';
  if (!PHONE_PATTERN.test(value)) return 'Phone number must be 10 to 15 digits and may start with +.';

  return '';
};

const validatePassword = (password) => {
  if (!password) return 'Please create a password.';
  if (password.length > 255) return 'Passwords cannot exceed 255 characters.';
  if (
    password.length < 8
    || !/[a-z]/.test(password)
    || !/[A-Z]/.test(password)
    || !/\d/.test(password)
    || !/[^A-Za-z0-9]/.test(password)
  ) {
    return 'Use at least 8 characters with uppercase, lowercase, a number, and a symbol.';
  }

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
  phone = '',
  password,
  password_confirmation: passwordConfirmation,
  role,
  department,
  student_id: studentId = '',
  faculty_id: facultyId = '',
}) => {
  const errors = {};
  const trimmedName = name.trim();
  const trimmedDepartment = department.trim();
  const normalizedStudentId = studentId.trim().toUpperCase();
  const normalizedFacultyId = facultyId.trim().toUpperCase();
  const emailError = validateEmail(email);
  const phoneError = validatePhone(phone);
  const passwordError = validatePassword(password);

  if (!trimmedName) {
    errors.name = 'Please enter your full name.';
  } else if (trimmedName.length < 2) {
    errors.name = 'Your full name must be at least 2 characters.';
  } else if (trimmedName.length > 255) {
    errors.name = 'Your full name cannot exceed 255 characters.';
  }

  if (emailError) errors.email = emailError;
  if (phoneError) errors.phone = phoneError;

  if (!ALLOWED_ROLES.includes(role)) {
    errors.role = 'Please select Student or Faculty.';
  }

  if (!trimmedDepartment) {
    errors.department = 'Please enter your department.';
  } else if (trimmedDepartment.length > 255) {
    errors.department = 'Department cannot exceed 255 characters.';
  }

  if (role === 'student') {
    if (!normalizedStudentId) {
      errors.student_id = 'Please enter your Student ID.';
    } else if (!STUDENT_ID_PATTERN.test(normalizedStudentId)) {
      errors.student_id = 'Student ID must use 2-5 uppercase letters followed by 8-12 digits, like CE66334459156.';
    }
  }

  if (role === 'faculty') {
    if (!normalizedFacultyId) {
      errors.faculty_id = 'Please enter your Faculty ID.';
    } else if (!FACULTY_ID_PATTERN.test(normalizedFacultyId)) {
      errors.faculty_id = 'Faculty ID must look like FAC-CSE-0045, FAC-EEE-0145, or FAC-CE-0045.';
    }
  }

  if (passwordError) errors.password = passwordError;

  if (!passwordConfirmation) {
    errors.password_confirmation = 'Please confirm your password.';
  } else if (passwordConfirmation !== password) {
    errors.password_confirmation = 'The password confirmation does not match.';
  }

  return errors;
};

export const validateCreateAdminForm = ({ name, email, phone = '', password, admin_id: adminId }) => {
  const errors = {};
  const trimmedName = name.trim();
  const normalizedAdminId = adminId.trim().toUpperCase();
  const emailError = validateEmail(email);
  const phoneError = validatePhone(phone, 'Please enter the admin phone number.');
  const passwordError = validatePassword(password);

  if (!trimmedName) {
    errors.name = 'Please enter the admin name.';
  } else if (trimmedName.length < 2) {
    errors.name = 'Admin name must be at least 2 characters.';
  } else if (trimmedName.length > 255) {
    errors.name = 'Admin name cannot exceed 255 characters.';
  }

  if (emailError) errors.email = emailError;
  if (phoneError) errors.phone = phoneError;
  if (passwordError) errors.password = passwordError;

  if (!normalizedAdminId) {
    errors.admin_id = 'Please enter the Admin ID.';
  } else if (!ADMIN_ID_PATTERN.test(normalizedAdminId)) {
    errors.admin_id = 'Admin ID must look like ADM-045.';
  }

  return errors;
};