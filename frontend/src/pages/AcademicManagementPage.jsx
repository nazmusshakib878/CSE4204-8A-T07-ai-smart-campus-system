import { useCallback, useEffect, useMemo, useState } from 'react';
import Layout from '../components/Layout';
import { useAuth } from '../auth/auth-context';
import { EmptyState, LoadingState, StatusAlert } from '../components/Feedback';
import {
  createCourse, deleteCourse, enrollCourseStudents, getAcademicManagement,
  getCourseWorkspace, removeCourseEnrollment, saveCourseAttendance,
  saveCourseAssessments, saveCourseGrades, saveStudentPerformance, updateCourse,
} from '../services/api';

const YEAR = new Date().getFullYear();
const SEMESTERS = ['Spring', 'Fall'];
const GRADES = ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'D', 'F'];
const EMPTY_COURSE = { course_code: '', title: '', department: '', credit_hours: 3, description: '', faculty_id: '', is_active: true };

function AcademicManagementPage() {
  const { user } = useAuth();
  const isAdmin = user?.role === 'admin';
  const [data, setData] = useState({ courses: [], students: [], faculty: [] });
  const [courseId, setCourseId] = useState('');
  const [workspace, setWorkspace] = useState(null);
  const [tab, setTab] = useState('courses');
  const [courseForm, setCourseForm] = useState(EMPTY_COURSE);
  const [editingId, setEditingId] = useState(null);
  const [term, setTerm] = useState({ semester: 'Spring', year: YEAR });
  const [picked, setPicked] = useState([]);
  const [date, setDate] = useState(new Date().toISOString().slice(0, 10));
  const [attendance, setAttendance] = useState({});
  const [gradeMap, setGradeMap] = useState({});
  const [assessmentMap, setAssessmentMap] = useState({});
  const [performance, setPerformance] = useState({});
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState('');
  const [feedback, setFeedback] = useState(null);
  const [errors, setErrors] = useState({});

  const loadOverview = useCallback(async () => {
    setLoading(true);
    try {
      const response = await getAcademicManagement();
      const next = response.data.data || { courses: [], students: [], faculty: [] };
      setData(next);
      setCourseId((current) => next.courses.some((course) => String(course.id) === String(current)) ? current : next.courses[0]?.id || '');
    } catch (error) {
      setFeedback({ variant: 'danger', message: error.message });
    } finally { setLoading(false); }
  }, []);

  const loadWorkspace = useCallback(async (id) => {
    if (!id) return setWorkspace(null);
    try {
      const response = await getCourseWorkspace(id);
      const next = response.data.data;
      setWorkspace(next);
      setAttendance(Object.fromEntries(next.enrollments.map((item) => [item.student.id, 'present'])));
      setGradeMap(Object.fromEntries(next.enrollments.map((item) => [item.student.id, item.grades[0]?.grade || 'A'])));
      setAssessmentMap(Object.fromEntries(next.enrollments.map((item) => {
        const record = item.assessments?.[0] || {};
        return [item.student.id, { quiz_marks: record.quiz_marks ?? 0, assignment_marks: record.assignment_marks ?? 0, mid_marks: record.mid_marks ?? 0, final_marks: record.final_marks ?? 0 }];
      })));
      setPerformance(Object.fromEntries(next.enrollments.map((item) => {
        const metric = item.performance[0] || {};
        return [item.student.id, { current_semester: item.student.current_semester ?? 1, cgpa: metric.cgpa ?? '', completed_credits: metric.completed_credits ?? '' }];
      })));
    } catch (error) { setFeedback({ variant: 'danger', message: error.message }); }
  }, []);

  useEffect(() => { loadOverview(); }, [loadOverview]);
  useEffect(() => { loadWorkspace(courseId); }, [courseId, loadWorkspace]);

  const action = async (key, callback, message, refresh = 'workspace') => {
    setBusy(key); setFeedback(null); setErrors({});
    try {
      await callback();
      if (refresh.includes('overview')) await loadOverview();
      if (refresh.includes('workspace') && courseId) await loadWorkspace(courseId);
      setFeedback({ variant: 'success', message });
      return true;
    } catch (error) {
      setErrors(error.fields || {});
      setFeedback({ variant: 'danger', message: error.message });
      return false;
    } finally { setBusy(''); }
  };

  const submitCourse = async (event) => {
    event.preventDefault();
    const payload = {
      ...courseForm, course_code: courseForm.course_code.trim().toUpperCase(),
      title: courseForm.title.trim(), department: courseForm.department.trim(),
      credit_hours: Number(courseForm.credit_hours), faculty_id: courseForm.faculty_id ? Number(courseForm.faculty_id) : null,
      is_active: Boolean(courseForm.is_active),
    };
    const ok = await action('course', () => editingId ? updateCourse(editingId, payload) : createCourse(payload), editingId ? 'Course updated.' : 'Course created.', 'overview');
    if (ok) { setCourseForm(EMPTY_COURSE); setEditingId(null); }
  };

  const editCourse = (course) => {
    setEditingId(course.id);
    setCourseForm({ course_code: course.course_code, title: course.title, department: course.department || '', credit_hours: course.credit_hours, description: course.description || '', faculty_id: course.faculty_id || '', is_active: course.is_active });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const enrolledIds = useMemo(() => new Set((workspace?.enrollments || []).map((item) => item.student.id)), [workspace]);
  const available = data.students.filter((student) => !enrolledIds.has(student.id));

  if (loading) return <Layout title="Academic Management"><LoadingState message="Loading academic data..." /></Layout>;

  const termInputs = (
    <div className="row g-2">
      <div className="col-7"><label className="form-label">Semester</label><select className="form-select" value={term.semester} onChange={(e) => setTerm({ ...term, semester: e.target.value })}>{SEMESTERS.map((value) => <option key={value}>{value}</option>)}</select></div>
      <div className="col-5"><label className="form-label">Year</label><input type="number" className="form-control" value={term.year} onChange={(e) => setTerm({ ...term, year: e.target.value })} /></div>
    </div>
  );

  return (
    <Layout title="Academic Management" subtitle="Courses, enrollment, attendance, grades, and semester performance.">
      {feedback && <StatusAlert variant={feedback.variant} message={feedback.message} onDismiss={() => setFeedback(null)} />}
      <section className="academic-management-hero mb-4">
        <div><span>ACADEMIC OPERATIONS</span><h3>{isAdmin ? 'University course control center' : 'Teaching data workspace'}</h3><p>Maintain verified academic records through one workflow.</p></div>
        <div className="academic-summary"><strong>{data.courses.length}</strong><span>{isAdmin ? 'System courses' : 'Assigned courses'}</span></div>
      </section>
      <div className="academic-tabs mb-4">
        {[['courses', 'Courses'], ['enrollment', 'Enrollment'], ['attendance', 'Attendance'], ['assessments', 'Assessment Marks'], ['results', 'Final Grades & CGPA']].map(([key, label]) => <button key={key} type="button" className={tab === key ? 'is-active' : ''} onClick={() => setTab(key)}>{label}</button>)}
      </div>

      {tab === 'courses' && <>
        {isAdmin && <form className="faculty-panel mb-4" onSubmit={submitCourse}>
          <h4>{editingId ? 'Edit Course' : 'Create Course & Assign Faculty'}</h4>
          <div className="row g-3">
            <div className="col-md-3"><label className="form-label">Course code</label><input className={`form-control${errors.course_code ? ' is-invalid' : ''}`} value={courseForm.course_code} onChange={(e) => setCourseForm({ ...courseForm, course_code: e.target.value })} required />{errors.course_code && <div className="invalid-feedback">{errors.course_code}</div>}</div>
            <div className="col-md-5"><label className="form-label">Title</label><input className="form-control" value={courseForm.title} onChange={(e) => setCourseForm({ ...courseForm, title: e.target.value })} required /></div>
            <div className="col-md-4"><label className="form-label">Department</label><input className="form-control" value={courseForm.department} onChange={(e) => setCourseForm({ ...courseForm, department: e.target.value })} required /></div>
            <div className="col-md-3"><label className="form-label">Credit hours</label><input type="number" min=".5" max="12" step=".5" className="form-control" value={courseForm.credit_hours} onChange={(e) => setCourseForm({ ...courseForm, credit_hours: e.target.value })} /></div>
            <div className="col-md-5"><label className="form-label">Assigned faculty</label><select className="form-select" value={courseForm.faculty_id} onChange={(e) => setCourseForm({ ...courseForm, faculty_id: e.target.value })}><option value="">Unassigned</option>{data.faculty.map((member) => <option key={member.id} value={member.id}>{member.name} · {member.faculty_id}</option>)}</select></div>
            <div className="col-md-4 d-flex align-items-end"><label className="form-check mb-2"><input type="checkbox" className="form-check-input" checked={courseForm.is_active} onChange={(e) => setCourseForm({ ...courseForm, is_active: e.target.checked })} /> Active course</label></div>
            <div className="col-12"><label className="form-label">Description</label><textarea className="form-control" rows="2" value={courseForm.description} onChange={(e) => setCourseForm({ ...courseForm, description: e.target.value })} /></div>
          </div>
          <div className="d-flex gap-2 mt-3"><button className="btn btn-primary" disabled={busy === 'course'}>{busy === 'course' ? 'Saving...' : editingId ? 'Update Course' : 'Create Course'}</button>{editingId && <button type="button" className="btn btn-outline-secondary" onClick={() => { setEditingId(null); setCourseForm(EMPTY_COURSE); }}>Cancel</button>}</div>
        </form>}
        {data.courses.length ? <section className="faculty-panel p-0 overflow-hidden"><div className="table-responsive"><table className="table faculty-table mb-0"><thead><tr><th>Course</th><th>Department</th><th>Faculty</th><th>Credits</th><th>Students</th><th>Actions</th></tr></thead><tbody>
          {data.courses.map((course) => <tr key={course.id}><td><strong>{course.course_code}</strong><small className="d-block">{course.title}</small></td><td>{course.department}</td><td>{course.faculty_name || 'Unassigned'}</td><td>{course.credit_hours}</td><td>{course.enrollment_count}</td><td><div className="d-flex gap-2"><button type="button" className="btn btn-sm btn-outline-primary" onClick={() => { setCourseId(course.id); setTab('enrollment'); }}>Open</button>{isAdmin && <button type="button" className="btn btn-sm btn-outline-secondary" onClick={() => editCourse(course)}>Edit</button>}{isAdmin && <button type="button" className="btn btn-sm btn-outline-danger" onClick={() => window.confirm(`Delete ${course.course_code}?`) && action('delete', () => deleteCourse(course.id), 'Course deleted.', 'overview')}>Delete</button>}</div></td></tr>)}
        </tbody></table></div></section> : <EmptyState title="No courses" message={isAdmin ? 'Create the first course.' : 'No course has been assigned to you.'} />}
      </>}

      {tab !== 'courses' && <>
        <section className="faculty-panel academic-course-selector mb-4"><label className="form-label">Working course</label><select className="form-select" value={courseId} onChange={(e) => setCourseId(e.target.value)}><option value="">Select course</option>{data.courses.map((course) => <option key={course.id} value={course.id}>{course.course_code} — {course.title}</option>)}</select></section>
        {!workspace ? <EmptyState title="Select a course" message="Choose a course to enter academic data." /> : tab === 'enrollment' ? <div className="row g-4">
          <div className="col-xl-5"><form className="faculty-panel h-100" onSubmit={async (e) => { e.preventDefault(); const ok = await action('enroll', () => enrollCourseStudents(courseId, { student_ids: picked, semester: term.semester, year: Number(term.year) }), 'Students enrolled.', 'overview workspace'); if (ok) setPicked([]); }}><h4>Enroll Students</h4>{termInputs}<div className="academic-student-picker mt-3">{available.map((student) => <label key={student.id}><input type="checkbox" checked={picked.includes(student.id)} onChange={(e) => setPicked((items) => e.target.checked ? [...items, student.id] : items.filter((id) => id !== student.id))} /><span><strong>{student.name}</strong><small>{student.student_number} · {student.department}</small></span></label>)}{!available.length && <p>All students are enrolled.</p>}</div><button className="btn btn-primary w-100 mt-3" disabled={!picked.length || busy === 'enroll'}>Enroll Selected</button></form></div>
          <div className="col-xl-7"><section className="faculty-panel h-100"><h4>Enrolled Students</h4>{workspace.enrollments.length ? <div className="academic-enrollment-list">{workspace.enrollments.map((item) => <article key={item.enrollment_id}><div><strong>{item.student.name}</strong><small>{item.student.student_number} · {item.semester} {item.year}</small></div><button type="button" className="btn btn-sm btn-outline-danger" onClick={() => action('remove', () => removeCourseEnrollment(courseId, item.enrollment_id), 'Student removed.', 'overview workspace')}>Remove</button></article>)}</div> : <EmptyState title="No students enrolled" message="Select students to enroll." />}</section></div>
        </div> : !workspace.enrollments.length ? <EmptyState title="No enrolled students" message="Enroll students first." /> : tab === 'attendance' ? <form className="faculty-panel" onSubmit={(e) => { e.preventDefault(); action('attendance', () => saveCourseAttendance(courseId, { attendance_date: date, records: workspace.enrollments.map((item) => ({ student_id: item.student.id, status: attendance[item.student.id] || 'present' })) }), 'Attendance saved.'); }}><div className="d-flex justify-content-between align-items-end mb-3"><h4>Daily Attendance</h4><div><label className="form-label">Date</label><input type="date" className="form-control" value={date} onChange={(e) => setDate(e.target.value)} /></div></div><div className="academic-entry-list">{workspace.enrollments.map((item) => <div key={item.student.id}><span><strong>{item.student.name}</strong><small>{item.student.student_number} · Overall {item.attendance_percentage}%</small></span><select className="form-select" value={attendance[item.student.id] || 'present'} onChange={(e) => setAttendance({ ...attendance, [item.student.id]: e.target.value })}><option value="present">Present</option><option value="late">Late</option><option value="absent">Absent</option></select></div>)}</div><button className="btn btn-primary mt-3" disabled={busy === 'attendance'}>Save Attendance</button></form> : tab === 'assessments' ? <form className="faculty-panel" onSubmit={(e) => { e.preventDefault(); action('assessments', () => saveCourseAssessments(courseId, { semester: term.semester, year: Number(term.year), records: workspace.enrollments.map((item) => ({ student_id: item.student.id, ...(assessmentMap[item.student.id] || {}) })) }), 'Assessment marks saved; final grades and CGPA recalculated.'); }}><div className="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3"><div><h4>Assessment Marks</h4><p className="text-secondary mb-0">Quiz 15 + Assignment 15 + Mid 30 + Final 40 = 100</p></div>{termInputs}</div><div className="table-responsive"><table className="table align-middle"><thead><tr><th>Student</th><th>Quiz /15</th><th>Assignment /15</th><th>Mid /30</th><th>Final /40</th><th>Total</th></tr></thead><tbody>{workspace.enrollments.map((item) => { const marks = assessmentMap[item.student.id] || {}; const total = ['quiz_marks','assignment_marks','mid_marks','final_marks'].reduce((sum, key) => sum + Number(marks[key] || 0), 0); return <tr key={item.student.id}><td><strong>{item.student.name}</strong><small className="d-block text-secondary">{item.student.student_number}</small></td>{[['quiz_marks',15],['assignment_marks',15],['mid_marks',30],['final_marks',40]].map(([key,max]) => <td key={key}><input type="number" min="0" max={max} step=".01" className="form-control" value={marks[key] ?? 0} onChange={(e) => setAssessmentMap({ ...assessmentMap, [item.student.id]: { ...marks, [key]: Number(e.target.value) } })} /></td>)}<td><strong>{total.toFixed(2)}</strong></td></tr>; })}</tbody></table></div><button className="btn btn-primary" disabled={busy === 'assessments'}>{busy === 'assessments' ? 'Calculating...' : 'Save Marks & Calculate Results'}</button></form> : <div className="row g-4">
          <div className="col-xl-5"><form className="faculty-panel h-100" onSubmit={(e) => { e.preventDefault(); action('grades', () => saveCourseGrades(courseId, { semester: term.semester, year: Number(term.year), records: workspace.enrollments.map((item) => ({ student_id: item.student.id, grade: gradeMap[item.student.id] || 'A' })) }), 'Grades saved.'); }}><h4>Course Grades</h4>{termInputs}<div className="academic-entry-list compact mt-3">{workspace.enrollments.map((item) => <div key={item.student.id}><span><strong>{item.student.name}</strong><small>{item.student.student_number}</small></span><select className="form-select" value={gradeMap[item.student.id] || 'A'} onChange={(e) => setGradeMap({ ...gradeMap, [item.student.id]: e.target.value })}>{GRADES.map((grade) => <option key={grade}>{grade}</option>)}</select></div>)}</div><button className="btn btn-primary w-100 mt-3">Save Grades</button></form></div>
          <div className="col-xl-7"><section className="faculty-panel"><h4>Current Semester & Calculated CGPA</h4><p className="text-secondary">CGPA and completed credits are calculated automatically from published course results.</p><div className="academic-performance-list">{workspace.enrollments.map((item) => { const entry = performance[item.student.id] || {}; return <article key={item.student.id}><div><strong>{item.student.name}</strong><small>{item.student.student_number}</small></div>{[['current_semester', 'Semester (1-8)'], ['cgpa', 'CGPA'], ['completed_credits', 'Completed Credits']].map(([key, label]) => <label key={key}><span>{label}</span><input type="number" min={key === 'current_semester' ? 1 : 0} max={key === 'current_semester' ? 8 : key === 'cgpa' ? 4 : 300} step={key === 'cgpa' ? .01 : 1} className="form-control" readOnly={key !== 'current_semester'} value={entry[key] ?? ''} onChange={(e) => setPerformance({ ...performance, [item.student.id]: { ...entry, [key]: e.target.value } })} /></label>)}<button type="button" className="btn btn-sm btn-outline-primary" onClick={() => action('performance', () => saveStudentPerformance(courseId, item.student.id, { semester: term.semester, year: Number(term.year), current_semester: Number(entry.current_semester), cgpa: Number(entry.cgpa), completed_credits: Number(entry.completed_credits) }), 'CGPA and current semester updated.')}>Update</button></article>; })}</div></section></div>
        </div>}
      </>}
    </Layout>
  );
}
export default AcademicManagementPage;
