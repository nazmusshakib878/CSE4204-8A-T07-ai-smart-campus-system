export const ordinalSemester = (value) => {
  const semester = Number(value);
  if (!Number.isInteger(semester) || semester < 1 || semester > 8) return '';

  if (semester === 1) return '1st Semester';
  if (semester === 2) return '2nd Semester';
  if (semester === 3) return '3rd Semester';
  return `${semester}th Semester`;
};

export const formatCurrentSemester = (term, semesterNumber) => {
  const level = ordinalSemester(semesterNumber);
  return [term, level].filter(Boolean).join(' · ') || 'Not assigned';
};
